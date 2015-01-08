<?php
/**
 * The bootstrap.php file is included by all loaders. External dependencies and other startup logic
 * should be handled here.
 */
$defaulttz = ini_get('date.timezone');
if ($defaulttz === '') {
    $tz = \util\Settings::get('global/timezone');
    if ($tz === null) {
        $tz = 'America/Los_Angeles';
    }
    date_default_timezone_set($tz);
}
