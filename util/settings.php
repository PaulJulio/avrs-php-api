<?php
namespace util;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}

final class Settings {

	private static $settings;

	private static function init() {
		if (empty(self::$settings)) {
			self::$settings = parse_ini_file(__DIR__ . '/settings.ini', true);
		}
	}

	public static function get($key) {
		self::init();
		$path = array_reverse(explode('/', $key));
		$value = self::$settings;
		while (count($path)) {
			$key = array_pop($path);
			if (isset($value[$key])) {
				$value = $value[$key];
			} else {
				return null;
			}
		}
		return $value;
	}
}
