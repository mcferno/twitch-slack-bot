<?php
use Utils\Logger;
use Model\Streamer;

/** @global $config \Utils\Config */
include(dirname(__DIR__) . "/src/bootstrap.php");

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret",
    "token",
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
$token = $config->get("token");
$debug = $config->get("debug", false);

$streamers = Streamer::factoryFromConfig($config->get("streamers"));

// build API clients
$helixGuzzleClient = new NewTwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new NewTwitchApi\NewTwitchApi($helixGuzzleClient, $clientId, $clientSecret);

$streamsRequest = $newTwitchApi->getStreamsApi()->getStreams($token, [], Streamer::getAllUserIds($streamers));
$streamList = json_decode($streamsRequest->getBody()->getContents());

if (!empty($streamList) && !empty($streamList->data)) {

	$client = new GuzzleHttp\Client();
	$keystore = new Client\PersistentStore();

    foreach ($streamList->data as $onlineStream) {
		$existingStream = $keystore->getActiveTwitchStream($onlineStream->user_id);
		/** @var Streamer $streamer */
		$streamer = $streamers[$onlineStream->user_name];

		if ($existingStream === false) {
			if ($debug) {
				print_r($onlineStream);
			}
			Logger::write("Announcing {$onlineStream->user_name} to Slack..");
			$imageUrl = str_replace(["{width}", "{height}"], ["1280", "720"], $onlineStream->thumbnail_url);
			$title = str_replace(['"'], ["'"], $onlineStream->title);
			$gameLabel = !empty($onlineStream->game_name) ? " *{$onlineStream->game_name}*." : "";
			$gameLabelPlain = !empty($onlineStream->game_name) ? " {$onlineStream->game_name}" : "";

			$userProfile = $keystore->getUserProfile($onlineStream->user_id);
			$profileImage = !empty($userProfile) && !empty($userProfile->profile_image_url)
				? $userProfile->profile_image_url
				: $imageUrl;

			$leadInUserMention = !$streamer->hasSlackHandle() ? "<{$streamer->getFeedUrl()}|*{$streamer->getName()}*>" : "<@{$streamer->slackUserId}>";

			$jsonRequest = <<<REQUEST
{
	"text": "{$streamer->getName()} started streaming{$gameLabelPlain}",
	"blocks": [
		{
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "{$leadInUserMention} started streaming :crosshair:{$gameLabel}"
			}
		},
		{
			"type": "section",
			"block_id": "section567",
			"text": {
				"type": "mrkdwn",
				"text": "><{$streamer->getFeedUrl()}|*{$onlineStream->user_name}*> {$title}\\n><{$streamer->getFeedUrl()}|{$streamer->getFeedUrl()}>"
			},
			"accessory": {
				"type": "image",
				"image_url": "{$profileImage}",
				"alt_text": "User profile photo"
			}
		}
	]
}
REQUEST;

			$jsonRequestObj = json_decode($jsonRequest);

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
		} else if(!empty($onlineStream->game_id)
			&& $existingStream->game_id != $onlineStream->game_id
			&& !empty($onlineStream->game_name)) {

			if ($debug) {
				print_r($onlineStream);
			}
			Logger::write("Announcing {$onlineStream->user_name} game change to Slack..");
			$jsonRequest = <<<REQUEST
{
	"text": "{$streamer->getName()} launched {$onlineStream->game_name}",
	"blocks": [
		{
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "<{$streamer->getFeedUrl()}|*{$streamer->getName()}*> launched :crosshair: *{$onlineStream->game_name}*."
			}
		}
	]
}
REQUEST;

			$jsonRequestObj = json_decode($jsonRequest);

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
