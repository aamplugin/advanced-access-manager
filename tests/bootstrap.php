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

// This will hook up to RESTful API dispatch to properly authenticate user
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $headers = $request->get_headers();

    if (array_key_exists('authorization', $headers)) {
        $token = str_replace('Bearer ', '', $headers['authorization'][0]);

        if (AAM::api()->jwt->is_valid($token)) {
            $claims = AAM::api()->jwt->decode($token);

            // Setting current user
            wp_set_current_user($claims['user_id']);
        }
    }

    return $result;
}, 10, 3);

// Create a somewhat a clone of the administrator role to test functionality that
// can be restricted to not super admin user
if (!wp_roles()->is_role('subadmin')) {
    wp_roles()->add_role(
        'subadmin',
        'Sub Administrator', wp_roles()->get_role('administrator')->capabilities
    );
}