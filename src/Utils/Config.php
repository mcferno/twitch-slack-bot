<?php

namespace Utils;

/**
 * Manages the configuration settings for the app.
 */
class Config
{
	private $configCache;

	public function __construct()
	{
		$this->configCache =  json_decode(file_get_contents(APP_ROOT . "/config.json"), true);
	}

	/**
	 * Verifies if a single configuration exists
	 */
	public function hasKey($key)
	{
		return array_key_exists($key, $this->configCache);
	}

	/**
	 * Verifies if a set of configurations exist
	 */
	public function hasKeys($keys)
	{
		return count(array_intersect_key(array_flip($keys), $this->configCache)) === count($keys);
	}

	/**
	 * Get a configuration value, with an optional fallback if not found
	 */
	public function get($key, $defaultValue = null)
	{
		return $this->hasKey($key) ? $this->configCache[$key] : $defaultValue;
	}
}
