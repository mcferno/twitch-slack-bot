<?php

namespace Template;

class ChangeGame
{
	/**
	 * Prepares the announcement template to use on Slack to announce a change of game for a running stream.
	 */
	static function get(
		/** Configured account */
		\Model\Streamer $streamer,
		/** New game launched */
		\Twitch\OnlineStream $onlineStream,
	): object {
		return json_decode(<<<REQUEST
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
REQUEST);
	}
}
