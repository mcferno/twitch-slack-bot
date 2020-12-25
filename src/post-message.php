<?php
$root = dirname(__DIR__);
include("$root/vendor/autoload.php");

$config = json_decode(file_get_contents("$root/config.json"), true);

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret",
    "token",
    "streamers"
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

$clientId = $config["twitchClientId"];
$clientSecret = $config["twitchClientSecret"];
$token = $config["token"];

// build API clients
$helixGuzzleClient = new NewTwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new NewTwitchApi\NewTwitchApi($helixGuzzleClient, $clientId, $clientSecret);

// login for a token
// $tokenRequest = new NewTwitchApi\Auth\OauthApi($clientId, $clientSecret, $helixGuzzleClient);
// print_r($tokenRequest->getAppAccessToken());

$streamsRequest = $newTwitchApi->getStreamsApi()->getStreams($token, [], $config["streamers"]);
$streamList = json_decode($streamsRequest->getBody()->getContents());


/*

[id] => 41070255022
[user_id] => 62134739
[user_name] => FaZeBlaze
[game_id] => 512710
[game_name] => Call of Duty: Warzone
[type] => live
[title] => MERRY CHRISTMAS :) @FaZeBlaze
[viewer_count] => 4546
[started_at] => 2020-12-25T02:56:32Z
[language] => en
[thumbnail_url] => https://static-cdn.jtvnw.net/previews-ttv/live_user_fazeblaze-{width}x{height}.jpg
[tag_ids] => Array
    (
        [0] => 6ea6bca4-4712-4ab9-a906-e3336a9d8039
        [1] => fd8f5e68-42d3-4544-a273-5fc92dabc568
    )
*/

if (!empty($streamList) && !empty($streamList->data)) {
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
				"text": "{$onlineStream->title}\n<{$userStreamUrl}|{$userStreamUrl}>"
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

        $template = json_decode($jsonRequest);

        print_r($jsonRequest);
    }
}
