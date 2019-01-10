<?php

/**
 * Make sure that path to the PHPUnit is included in the PHP.ini include_path as well
 * as phpunit is installed on your machine
 */

// Autoloader for the PHPUnit Framework
spl_autoload_register(function($classname) {
    if (strpos($classname, 'PHPUnit') === 0) {
        $filepath = __DIR__ . '\\' . $classname . '.php';
        
        if (file_exists($filepath)) {
            require $filepath;
        }
    }
});

// Load the WordPress library.
require_once dirname(__DIR__) . '/../../../wp-load.php';

// Set up the WordPress query.
wp();