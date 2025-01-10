<?php
namespace Template;

class NewStream
{
	/**
	 * Prepares the announcement template to use on Slack to announce a new stream going online.
	 */
	static function get(
		/** Configured account */
		\Model\Streamer $streamer,
		/** Game launched */
		\Twitch\OnlineStream $onlineStream,
		/** Extended profile data */
		\Twitch\UserProfile|null $userProfile
	): object
	{
		$imageUrl = str_replace(["{width}", "{height}"], ["1280", "720"], $onlineStream->thumbnail_url);
		$title = str_replace(['"'], ["'"], $onlineStream->title);
		$gameLabel = !empty($onlineStream->game_name) ? " *{$onlineStream->game_name}*." : "";
		$gameLabelPlain = !empty($onlineStream->game_name) ? " {$onlineStream->game_name}" : "";

		$profileImage = !empty($userProfile) && !empty($userProfile->profile_image_url)
			? $userProfile->profile_image_url
			: $imageUrl;

		$leadInUserMention = !$streamer->hasSlackHandle() ? "<{$streamer->getFeedUrl()}|*{$streamer->getName()}*>" : "<@{$streamer->slackUserId}>";

		return json_decode(<<<REQUEST
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
REQUEST);
	}
}
