<?php

namespace Model;

/**
 * A single Twitch streamer account, driven by configuration input
 */
class Streamer
{
	/** @var string  */
	public $name;
	/** @var string */
	public $username;
	/** @var string */
	public $slackUserId;

	public function __construct($config)
	{
		if (is_string($config)) {
			$this->name = $this->username = $config;
		} elseif (is_array($config)) {
			$this->username = $config['username'];
			$this->name = !empty($config['name']) ? $config['name'] : $this->username;
			if (!empty($config['slackUserId'])) {
				$this->slackUserId = $config['slackUserId'];
			}
		}
	}

	public function getName()
	{
		return $this->name;
	}

	/**
	 * Link to their online feed
	 * @return string
	 */
	public function getFeedUrl()
	{
		return "https://twitch.tv/{$this->username}";
	}

	public function hasSlackHandle()
	{
		return !empty($this->slackUserId);
	}

	/**
	 * @param $allAccounts mixed Config-driven account set up
	 * @return Streamer[]
	 */
	public static function factoryFromConfig($allAccounts)
	{
		$accounts = [];
		foreach ($allAccounts as $account) {
			$account = new self($account);
			$accounts[$account->username] = $account;
		}
		return $accounts;
	}

	public static function getAllUserIds($steamers)
	{
		return array_reduce($steamers, function ($accum, Streamer $streamer) {
			$accum[] = $streamer->username;
			return $accum;
		}, []);
	}
}
