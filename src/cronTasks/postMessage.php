<?php

use Utils\Logger;
use Model\Streamer;

/** @global $config \Utils\Config */
include(dirname(dirname(__DIR__)) . "/src/bootstrap.php");

$requiredConfigKeys = [
	"twitchClientId",
	"twitchClientSecret",
	"streamers",
	"slackWebhookUrl"
];

// are we missing any configs?
if (!$config->hasKeys($requiredConfigKeys)) {
	Logger::write("Config must contain: " . implode(" | ", $requiredConfigKeys) . "\nExiting..");
	exit(2);
}

if (empty($config->get("streamers"))) {
	Logger::write("Not configured to pull any streamers. Exiting..");
	exit(0);
}

$clientId = $config->get("twitchClientId");
$clientSecret = $config->get("twitchClientSecret");

$keystore = new Client\PersistentStore();
$token = $keystore->getTwitchAuthToken();

if (empty($token)) {
	Logger::write("Can't proceed with an empty Twitch access token, exiting");
	exit(1);
}

$debug = $config->get("debug", false);

$streamers = Streamer::factoryFromConfig($config->get("streamers"));

// build API clients
$helixGuzzleClient = new TwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new TwitchApi\TwitchApi($helixGuzzleClient, $clientId, $clientSecret);

$streamsRequest = $newTwitchApi->getStreamsApi()->getStreams($token, [], Streamer::getAllUserIds($streamers));
$streamList = json_decode($streamsRequest->getBody()->getContents());

if (!empty($streamList) && !empty($streamList->data)) {

	$client = new GuzzleHttp\Client();
	$keystore = new Client\PersistentStore();

	foreach ($streamList->data as $onlineStream) {
		$existingStream = $keystore->getActiveTwitchStream($onlineStream->user_id);
		/** @var Streamer $streamer */
		$streamer = $streamers[$onlineStream->user_name];

		if (empty($streamer)) {
			Logger::write("Could not find the config-driven profile for {$onlineStream->user_name}. Skipping ..");
			print_r($onlineStream);
			continue;
		}

		if ($existingStream === false) {
			if ($debug) {
				print_r($onlineStream);
			}
			Logger::write("Announcing {$onlineStream->user_name} to Slack..");

			$profileRaw = $keystore->getUserProfile($onlineStream->user_id);
			$jsonRequestObj = Template\NewStream::get(
				$streamer,
				new Twitch\OnlineStream($onlineStream),
				!empty($profileRaw) ? new Twitch\UserProfile($profileRaw) : null
			);

			if (empty($jsonRequestObj)) {
				Logger::write("Failed to build a valid JSON message for Slack for {$onlineStream->user_name}. Skipping ..");
				print_r($onlineStream);
				continue;
			}

			$slackPostResponse = $client->request('POST', $config->get("slackWebhookUrl"), [
				'json' => $jsonRequestObj
			]);

			if ($slackPostResponse->getStatusCode() !== 200) {
				Logger::write("Failed to announce go live for stream {$onlineStream->user_name}");
			}

			// stream is already running, see if we need to announce a game change
		} else if (
			!empty($onlineStream->game_id)
			&& $existingStream->game_id != $onlineStream->game_id
			&& !empty($onlineStream->game_name)
		) {

			if ($debug) {
				print_r($onlineStream);
			}
			Logger::write("Announcing {$onlineStream->user_name} game change to Slack..");
			$jsonRequestObj = Template\ChangeGame::get($streamer, new Twitch\OnlineStream($onlineStream));

			if (empty($jsonRequestObj)) {
				Logger::write("Failed to build a valid JSON message for Slack for {$onlineStream->user_name}. Skipping ..");
				print_r($onlineStream);
				continue;
			}

			$slackPostResponse = $client->request('POST', $config->get("slackWebhookUrl"), [
				'json' => $jsonRequestObj
			]);

			if ($slackPostResponse->getStatusCode() !== 200) {
				Logger::write("Failed to annouce game change for stream {$onlineStream->user_name}");
			}
		}

		// remember this streamer so we don't annouce twice
		$result = $keystore->setActiveTwitchStream($onlineStream->user_id, $onlineStream);
		if ($result !== true) {
			Logger::write("Could not save {$onlineStream->user_id} state to Redis.");
		}
	}
}

// Logger::write("Exiting normally.");
