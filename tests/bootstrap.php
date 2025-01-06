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

// Defining the global constant that disabled AAM internal object cache
define('AAM_OBJECT_CACHE_ENABLED', false);

// Load the WordPress library & some additional files.
require_once dirname(__DIR__) . '/../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Create a somewhat a clone of the administrator role to test functionality that
// can be restricted to not super admin user
if (!wp_roles()->is_role('subadmin')) {
    wp_roles()->add_role(
        'subadmin',
        'Sub Administrator', wp_roles()->get_role('administrator')->capabilities
    );
}