# Twitch-to-Slack notification bot

This app monitors Twitch for streamers you want to subscribe to. When any of these streamers go live, or changes game during a stream, a notification will be posted to Slack.

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
