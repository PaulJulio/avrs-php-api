<?php
namespace util;

final class Loader {
	// all files are expected to have a namespace that matches their containing directory
	// or be in the root directory (one above this one)
	// All file and directory names are lower case, while the actual classes may be mixed case
	public static function loadWithNameSpace($class) {
		$root = realpath(__DIR__ . '/../');
		$chunks = explode('\\', $class);
		$fn = strtolower($root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $chunks) . '.php');
		if (file_exists($fn)) {
			include_once($fn);
		}
	}
}
spl_autoload_register('\util\Loader::loadWithNameSpace');
