<?php

/**
 * Make sure that path to the PHPUnit is included in the PHP.ini include_path as well
 * as PHPUnit is installed on your machine
 */

// Autoloader for the PHPUnit Framework
spl_autoload_register(function ($class_name) {
    $filepath = null;

    if (strpos($class_name, 'PHPUnit') === 0) {
        $filepath = __DIR__ . '\\' . $class_name . '.php';
    } elseif (strpos($class_name, 'AAM\UnitTest') === 0) {
        $filepath  = __DIR__;
        $filepath .= str_replace(
            [ 'AAM\UnitTest', '\\' ], [ '', '/' ], $class_name
        ) . '.php';
    }

    if ($filepath && file_exists($filepath)) {
        require $filepath;
    }
});

// Load the WordPress library & some additional files.
require_once dirname(__DIR__) . '/../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';