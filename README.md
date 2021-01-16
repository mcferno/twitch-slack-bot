# Twitch-to-Slack notification bot

This app monitors Twitch for streamers you want to subscribe to. When any of these streamers go live, or changes game during a stream, a notification will be posted to Slack.

## Requirements

1. PHP 7.2+, with [Composer](https://getcomposer.org/download/) installed
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
    "twitchClientSecret": "string",
}
```

Set these according to a Twitch App you create via their [Developer portal](https://dev.twitch.tv/console/apps/create)

### Slack webhook (required)

Sets the destination for the Twitch annoucement posts. Controls the Slack organization, and channel.

```json
{
    "slackWebhookUrl": "https://hooks.slack.com/services/ ..."
}
```

### Twitch Streamer List (required)

Lists of streamers you are subscribing to

```json
{
    "streamers": [
		"FaZeBlaze",
		"Swagg",
		"cloakzy",
		"NICKMERCS"
	]
}
```

## Setup

Cronjob is the easiest way to run the script during periods you'd like to have notifications.

Have a look at a [crontab generator](https://crontab-generator.org/) if you need a hand with the syntax.

Example, checks every 60 seconds, 24/7:

```bash
# Notification script. Runs every 60 seconds, every day
* * * * * /usr/bin/php -f ./twitch-slack-bot/src/post-message.php >> twitch-bot-notify-script.log

# User profile script. Only need to run it once a day to get any profile image, name, or bio changes
0 2 * * * /usr/bin/php -f ./twitch-slack-bot/src/get-user-profile.php >> twitch-bot-user-script.log
```

Example: To run 9am to 9pm, Monday through Friday, use `* 9-21 * * 1-5`.