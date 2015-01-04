<?php
namespace util;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}

final class Timezone {

    /**
     * @param string $tz The timezone to use, or null to load from ini
     */
    public static function setIfNoDefault($tz = null) {
        $default = ini_get('date.timezone');
        if ($default !== '') {
            return;
        }
        if (!isset($tz)) {
            $tz = Settings::get('global/timezone');
            if ($tz === null) {
                $tz = 'America/Los_Angeles';
            }
        }
        date_default_timezone_set($tz);
    }
}
