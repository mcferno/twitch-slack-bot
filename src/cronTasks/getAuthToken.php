<?php
use Utils\Logger;

/** @global $config \Utils\Config */
include(dirname(dirname(__DIR__)) . "/src/bootstrap.php");

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret"
];

// are we missing any configs?
if (!$config->hasKeys($requiredConfigKeys)) {
    Logger::write("Config must contain: " . implode(" | ", $requiredConfigKeys) . "\nExiting..");
    exit(2);
}

$tokenRequest = new TwitchApi\Auth\OauthApi(
    $config->get("twitchClientId"),
    $config->get("twitchClientSecret"),
    new Client\TwitchAuthClient($config->get("twitchClientId"))
);

$tokenResponse = $tokenRequest->getAppAccessToken();
if ($tokenResponse->getStatusCode() !== 200) {
    Logger::write("Cant get new Bearer token from Twitch app auth");
    Logger::write($tokenResponse->getReasonPhrase());
    exit(3);
}

$tokenResponseBody = json_decode($tokenResponse->getBody()->getContents());
Logger::write($tokenResponseBody->access_token);

$keystore = new Client\PersistentStore();
if ($keystore->setTwitchAuthToken($tokenResponseBody->access_token)) {
	Logger::write("Successfully stored the new auth token.");
}

Logger::write("Expiry estimate: " . date("Y-m-d H:i:s", strtotime("now + {$tokenResponseBody->expires_in} seconds")));
