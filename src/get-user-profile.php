<?php
use Utils\Logger;
include(dirname(__DIR__) . "/src/bootstrap.php");

$requiredConfigKeys = [
    "twitchClientId",
    "twitchClientSecret",
    "token",
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
$token = $config->get("token");
$debug = $config->get("debug", false);

// build API clients
$helixGuzzleClient = new NewTwitchApi\HelixGuzzleClient($clientId);
$newTwitchApi = new NewTwitchApi\NewTwitchApi($helixGuzzleClient, $clientId, $clientSecret);

$userListRequest = $newTwitchApi->getUsersApi()->getUsers($token, [], $config->get("streamers"));
$userListResponse = json_decode($userListRequest->getBody()->getContents());

if (empty($userListResponse) || empty($userListResponse->data)) {
	Logger::write("Profile response empty. Exiting.");
    exit(0);
}

$keystore = new Client\PersistentStore();
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
