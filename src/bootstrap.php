<?php
/**
 * Configures some of the essentials
 */

// full path to the root of the application
if (!defined("APP_ROOT")) {
	define("APP_ROOT", dirname(__DIR__));
}

// load composer
include(APP_ROOT . "/vendor/autoload.php");

// we require the existence of config.json to process
if (!file_exists(APP_ROOT . "/config.json")) {
	\Utils\Logger::write("/config.json not found. Please create it from the config.sample.json file.");
	exit(1);
}

$config = new \Utils\Config();
date_default_timezone_set($config->get("timezone", "UTC"));
