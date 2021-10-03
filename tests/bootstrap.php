<?php

/**
 * Make sure that path to the PHPUnit is included in the PHP.ini include_path as well
 * as PHPUnit is installed on your machine
 */

// Autoloader for the PHPUnit Framework
spl_autoload_register(function ($classname) {
    $filepath = null;

    if (strpos($classname, 'PHPUnit') === 0) {
        $filepath = __DIR__ . '\\' . $classname . '.php';
    } elseif (strpos($classname, 'AAM\UnitTest') === 0) {
        $filepath = __DIR__ . str_replace(array('AAM\UnitTest', '\\'), array('', '/'), $classname) . '.php';
    }

    if ($filepath && file_exists($filepath)) {
        require $filepath;
    }
});

// Set the placeholder for the emulated headers
$GLOBALS['UT_HTTP_HEADERS'] = array();

/**
 * Mock the wp_redirect
 *
 * @param string  $location
 * @param integer $status
 * @param string  $x_redirect_by
 *
 * @return void
 */
function wp_redirect($location) {
    if (!isset($GLOBALS['UT_HTTP_HEADERS'])) {
        $GLOBALS['UT_HTTP_HEADERS'] = array();
    }

    array_push($GLOBALS['UT_HTTP_HEADERS'], 'Location: ' . $location);
}

// Load the WordPress library.
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Very important to allow to test headers
ob_start();