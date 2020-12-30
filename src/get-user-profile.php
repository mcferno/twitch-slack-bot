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

/*
(
	[id] => 81687332
	[login] => cloakzy
	[display_name] => cloakzy
	[type] =>
	[broadcaster_type] => partner
	[description] => Battle Royale Pro Player.
	[profile_image_url] => https://static-cdn.jtvnw.net/jtv_user_pictures/320226c6-f422-4baf-8ed2-1be7eb3757e6-profile_image-300x300.png
	[offline_image_url] => https://static-cdn.jtvnw.net/jtv_user_pictures/05c63a18-e600-4809-b3f1-91ad0cca4e04-channel_offline_image-1920x1080.jpeg
	[view_count] => 43529865
	[created_at] => 2015-02-03T07:50:36.963613Z
)
*/

$updatedUsers = [];
foreach ($userListResponse->data as $userProfile) {
	if (empty($userProfile->login)) {
		Logger::write("Can't find the login on this User profile object");
		print_r($userProfile);
		continue;
	}

	$keystore->setUserProfile($userProfile->login, $userProfile);
	$updatedUsers[] = $userProfile->login;
}

Logger::write("Updating " . count($updatedUsers) . " user profiles: " . implode(", " ,$updatedUsers));
