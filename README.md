# Twitch-to-Slack notification bot

This app monitors Twitch for streamers who's stream you want to subscribe to. When any of these streamers go live, or changes game during a stream, a notification will be posted to a Slack organization and channel of your choosing.

## Requirements

1. PHP 7.4+, with [Composer](https://getcomposer.org/download/) installed
2. Redis server instance
3. Cronjob access

## Configuration

Modify the `config.json` file (copy `config.sample.json` to create it the first time).

If you have any troubles formatting the file, you can use [an online tool](https://jsonformatter.curiousconcept.com/) to ensure your JSON format is valid

### Twitch API credentials (required)

Provides API access to Twitch which holds the stream & streamer information

```json
{
	"twitchClientId": "string",
	"twitchClientSecret": "string"
}
```

Set these according to a Twitch App you create via their [Developer portal](https://dev.twitch.tv/console/apps/create)

### Slack webhook (required)

Sets the destination for the Twitch annoucement posts. Controls the Slack organization, and channel.

Follow Slack's steps to obtain a unique URL for your Slack organization: https://api.slack.com/messaging/webhooks

```json
{
	"slackWebhookUrl": "https://hooks.slack.com/services/ ..."
}
```

### Twitch Streamer List (required)

Lists of streamers you are subscribing to

```json
{
	"streamers": ["FaZeBlaze", "Swagg", "cloakzy", "NICKMERCS"]
}
```

#### Advanced Streamer List (optional)

Each of the streamers can be expressed in a more advanced structure which permits additional configuration:

```json
{
	"username": "TwitchHandle",
	"name": "CustomNameForSlackAnnouncement",
	"slackUserId": "UXXXXXXXXX"
}
```

-   `username`: The Twitch stream handle, same value you'd normally have in the plain array in the simple example above.
-   `name`: Change the name of the streamer when announcing on Slack.
-   `slackUserId`: If set, the Slack announcement will @mention the user within the post. Useful when the steamer is part of the Slack org and wishes to be notified of replies to the announcement.

You may mix both configuration formats:

```json
{
	"streamers": [
		"FaZeBlaze",
		"Swagg",
		"cloakzy",
		{
			"username": "MyBuddyTom",
			"name": "Tommy D",
			"slackUserId": "U12345678"
		}
	]
}
```

## Setup

Cronjob is the easiest way to run the script during periods you'd like to have notifications.

Have a look at a [crontab generator](https://crontab-generator.org/) if you need a hand with the syntax.

Example, checks every 60 seconds, 24/7:

```bash
# Fetch a Twitch API access token every day, storing it for use by the every-minute announcement bot (below)
0 1 * * * /usr/bin/php -f ./twitch-slack-bot/src/cronTasks/getAuthToken.php >> twitch-bot-user-script.log

# User profile script. Only need to run it once a day to get any profile image, name, or bio changes
0 2 * * * /usr/bin/php -f ./twitch-slack-bot/src/cronTasks/getUserProfile.php >> twitch-bot-user-script.log

# Notification script. Runs every 60 seconds, every day
* * * * * /usr/bin/php -f ./twitch-slack-bot/src/cronTasks/postMessage.php >> twitch-bot-notify-script.log
```

Example: To run 9am to 9pm, Monday through Friday, use `* 9-21 * * 1-5`.
