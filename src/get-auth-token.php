<?php
$root = dirname(__DIR__);
include("$root/vendor/autoload.php");

$config = json_decode(file_get_contents("$root/config.json"), true);

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret"
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

$tokenRequest = new NewTwitchApi\Auth\OauthApi(
    $config["twitchClientId"],
    $config["twitchClientSecret"],
    new Client\TwitchAuthClient($config["twitchClientId"])
);
$tokenResponse = $tokenRequest->getAppAccessToken();
if ($tokenResponse->getStatusCode() !== 200) {
    echo "Cant get new Bearer token from Twitch app auth";
    echo $tokenResponse->getReasonPhrase();
    exit(3);
}

$tokenResponseBody = json_decode($tokenResponse->getBody()->getContents());
echo $tokenResponseBody->access_token;
echo "\n";
echo $tokenResponseBody->expires_in;
echo "\n";
echo date("Y-m-d H:i:s", strtotime("now + {$tokenResponseBody->expires_in} seconds"));
