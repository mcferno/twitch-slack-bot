<?php

namespace Client;

class TwitchAuthClient extends \GuzzleHttp\Client
{
	private const BASE_URI = 'https://id.twitch.tv/oauth2/';

	public function __construct(string $clientId, array $config = [])
	{
		parent::__construct($config + [
			'base_uri' => self::BASE_URI,
			'timeout' => 30,
			'headers' => ['Client-ID' => $clientId, 'Content-Type' => 'application/json'],
		]);
	}
}
