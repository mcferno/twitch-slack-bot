<?php
use Utils\Logger;
use Model\Streamer;

/** @global $config \Utils\Config */
include(dirname(dirname(__DIR__)) . "/src/bootstrap.php");

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret",
	"streamers"
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

$userListRequest = $newTwitchApi->getUsersApi()->getUsers($token, [], Streamer::getAllUserIds($streamers));
$userListResponse = json_decode($userListRequest->getBody()->getContents());

if (empty($userListResponse) || empty($userListResponse->data)) {
	Logger::write("Profile response empty. Exiting.");
    exit(0);
}

$updatedUsers = [];
foreach ($userListResponse->data as $userProfile) {
	if (empty($userProfile->login)) {
		Logger::write("Can't find the login on this User profile object");
		print_r($userProfile);
		continue;
	}

	$keystore->setUserProfile($userProfile->id, $userProfile);
	$updatedUsers[] = $userProfile->login;
}

Logger::write("Updating " . count($updatedUsers) . " user profiles: " . implode(", " ,$updatedUsers));
