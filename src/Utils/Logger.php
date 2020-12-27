<?php

namespace Utils;

class Logger
{
	public static function write($message = "") {
		echo "[" . date("Y-m-d H:i:s") . "]: " . $message . "\n";
	}
}