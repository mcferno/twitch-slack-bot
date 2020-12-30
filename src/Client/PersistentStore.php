<?php
namespace Client;
class PersistentStore
{
	protected $redis;

	const AUTH_TOKEN_KEY = "twitch_auth";
	const AUTH_TOKEN_TTL = 7200; // 2 hours

	const USER_STREAM_KEY = "stream_by_userid_";
	const USER_STREAM_TTL = 600; // 10 minutes

	const USER_PROFILE_KEY = "user_profile_";
	const USER_PROFILE_TTL = 14 * 86400; // 2 weeks

	public function __construct($connectionUri = "") {
		$this->redis = new \Redis();
		$this->redis->connect('127.0.0.1', 6379);
	}

	public function __destruct() {
		$this->redis->close();
	}

	public function getTwitchAuthToken() {
		return $this->redis->get(self::AUTH_TOKEN_KEY);
	}

	public function setTwitchAuthToken($value) {
		return $this->redis->setEx(self::AUTH_TOKEN_KEY, self::AUTH_TOKEN_TTL, $value);
	}

	public function getActiveTwitchStream($userId) {
		$obj = $this->redis->get($this->getActiveStreamKey($userId));
		if (!empty($obj)) {
			return json_decode($obj);
		}
		return $obj;
	}

	public function setActiveTwitchStream($userId, $payload) {
		return $this->redis->setEx($this->getActiveStreamKey($userId), self::USER_STREAM_TTL, json_encode($payload));
	}

	protected function getActiveStreamKey($userId) {
		return self::USER_STREAM_KEY . $userId;
	}

	public function getUserProfile($username) {
		$obj = $this->redis->get($this->getUserProfileKey($username));
		if (!empty($obj)) {
			return json_decode($obj);
		}
		return $obj;
	}

	public function setUserProfile($username, $payload) {
		return $this->redis->setEx($this->getUserProfileKey($username), self::USER_PROFILE_TTL, json_encode($payload));
	}

	protected function getUserProfileKey($username) {
		return self::USER_PROFILE_KEY . $username;
	}
}