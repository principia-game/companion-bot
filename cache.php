<?php

class Cache {
	private static $data;

	public static function init($cat) {
		self::$data[$cat] = [];
	}

	public static function get($cat, $key) {
		return self::$data[$cat][$key] ?? null;
	}

	public static function set($cat, $key, $value) {
		self::$data[$cat][$key] = $value;
	}
}
