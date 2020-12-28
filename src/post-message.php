<?php
use Utils\Logger;
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

// build API clients
$helixGuzzleClient = new NewTwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new NewTwitchApi\NewTwitchApi($helixGuzzleClient, $clientId, $clientSecret);

$streamsRequest = $newTwitchApi->getStreamsApi()->getStreams($token, [], $config->get("streamers"));
$streamList = json_decode($streamsRequest->getBody()->getContents());

if (!empty($streamList) && !empty($streamList->data)) {

	$client = new GuzzleHttp\Client();
	$keystore = new Client\PersistentStore();

    foreach ($streamList->data as $onlineStream) {
		$existingStream = $keystore->getActiveTwitchStream($onlineStream->user_id);

		if ($existingStream === false) {
			Logger::write("Announcing {$onlineStream->user_name} to Slack..");
			$userStreamUrl = "https://twitch.tv/{$onlineStream->user_name}";
			$imageUrl = str_replace(["{width}", "{height}"], ["1280", "720"], $onlineStream->thumbnail_url);
			$title = str_replace(['"'], ["'"], $onlineStream->title);

			$jsonRequest = <<<REQUEST
{
	"text": "{$onlineStream->user_name} started streaming {$onlineStream->game_name}",
	"blocks": [
		{
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "<{$userStreamUrl}|*{$onlineStream->user_name}*> started streaming :crosshair: *{$onlineStream->game_name}*."
			}
		},
		{
			"type": "section",
			"block_id": "section567",
			"text": {
				"type": "mrkdwn",
				"text": "{$title}\\n<{$userStreamUrl}|{$userStreamUrl}>"
			},
			"accessory": {
				"type": "image",
				"image_url": "{$imageUrl}",
				"alt_text": "Stream gameplay"
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
				Logger::write("Failed to annouce go live for stream {$onlineStream->user_name}");
			}

		// stream is already running, see if we need to announce a game change
		} else if(!empty($existingStream->game_id)
			&& !empty($onlineStream->game_id)
			&& $existingStream->game_id !== $onlineStream->game_id) {

			Logger::write("Announcing {$onlineStream->user_name} game change to Slack..");
			$userStreamUrl = "https://twitch.tv/{$onlineStream->user_name}";
			$jsonRequest = <<<REQUEST
{
	"text": "{$onlineStream->user_name} launched {$onlineStream->game_name}",
	"blocks": [
		{
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "<{$userStreamUrl}|*{$onlineStream->user_name}*> launched :crosshair: *{$onlineStream->game_name}*."
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

Logger::write("Exiting normally.");
