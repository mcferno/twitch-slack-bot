<?php
$root = dirname(__DIR__);
include("$root/vendor/autoload.php");

$config = json_decode(file_get_contents("$root/config.json"), true);

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret",
    "token",
	"streamers",
	"slackWebhookUrl"
];

if (empty($config)) {
    echo "Config file not found.\nExiting...";
    exit(1);
}

// are we missing any configs?
if (count(array_intersect_key(array_flip($requiredConfigKeys), $config)) !== count($requiredConfigKeys)) {
    echo "Config must contain: " . implode(" | ", $requiredConfigKeys) . "\nExiting..";
    exit(2);
}

if (empty($config["streamers"])) {
    echo "Not configured to pull any streamers. Exiting..";
    exit(0);
}

$clientId = $config["twitchClientId"];
$clientSecret = $config["twitchClientSecret"];
$token = $config["token"];

// build API clients
$helixGuzzleClient = new NewTwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new NewTwitchApi\NewTwitchApi($helixGuzzleClient, $clientId, $clientSecret);

$streamsRequest = $newTwitchApi->getStreamsApi()->getStreams($token, [], $config["streamers"]);
$streamList = json_decode($streamsRequest->getBody()->getContents());

if (!empty($streamList) && !empty($streamList->data)) {

	$client = new GuzzleHttp\Client();

    foreach ($streamList->data as $onlineStream) {
        print_r($onlineStream);

        $userStreamUrl = "https://twitch.tv/{$onlineStream->user_name}";
        $imageUrl = str_replace(["{width}", "{height}"], ["1280", "720"], $onlineStream->thumbnail_url);

        $jsonRequest = <<<REQUEST
{
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
				"text": "{$onlineStream->title}\\n<{$userStreamUrl}|{$userStreamUrl}>"
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

		$slackPostResponse = $client->request('POST', $config["slackWebhookUrl"], [
			'json' => json_decode($jsonRequest)
		]);

		if ($slackPostResponse->getStatusCode() !== 200) {
			echo "Failing to annouce for stream {$onlineStream->user_name}\n";
		}
    }
}
