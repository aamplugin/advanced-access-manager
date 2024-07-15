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

// Load the WordPress library & some additional files.
require_once dirname(__DIR__) . '/../../../wp-load.php';
require_once ABSPATH . '/wp-admin/includes/user.php';

// Prepare the list of users
global $wpdb;

// Resetting all users
$wpdb->query("TRUNCATE TABLE {$wpdb->users}");
$wpdb->query("TRUNCATE TABLE {$wpdb->usermeta}");

// Inserting the default user
$admin_user_id = wp_insert_user(array(
    'user_login' => AAM_UNITTEST_ADMIN_USERNAME,
    'user_email' => 'admin@testing.local',
    'first_name' => 'Default',
    'last_name'  => 'Administrator',
    'role'       => 'administrator',
    'user_pass'  => AAM_UNITTEST_ADMIN_PASSWORD
));

if (!is_wp_error($admin_user_id)) {
    define('AAM_UNITTEST_ADMIN_USER_ID', $admin_user_id);
    define('AAM_UNITTEST_USERNAME', AAM_UNITTEST_ADMIN_USERNAME);
    define('AAM_UNITTEST_PASSWORD', AAM_UNITTEST_ADMIN_PASSWORD);
}

// Creating a user with multiple roles
$multi_role_user_id = wp_insert_user(array(
    'user_login' => 'ut_multirole',
    'user_email' => 'utmultirole@testing.local',
    'first_name' => 'Multirole',
    'last_name'  => 'User',
    'role'       => 'subscriber',
    'user_pass'  => wp_generate_password()
));

if ($multi_role_user_id) {
    get_user_by('ID', $multi_role_user_id)->add_role('author');

    define('AAM_UNITTEST_MULTIROLE_USER_ID', $multi_role_user_id);
}

// Create an editor user
$editor_user_id = wp_insert_user(array(
    'user_login' => 'ut_editor',
    'user_email' => 'uteditor@testing.local',
    'first_name' => 'Editor',
    'last_name'  => 'User',
    'role'       => 'editor',
    'user_pass'  => wp_generate_password()
));

if ($editor_user_id) {
    get_user_by('ID', $editor_user_id)->add_cap('edit_users', true);

    define('AAM_UNITTEST_USER_EDITOR_USER_ID', $editor_user_id);
}

// Take control over wp_die
add_filter('aam_wp_die_args_filter', function($args) {
    if (is_array($args)) {
        $args['exit'] = false;
    } else {
        $args = [ 'exit' => false ];
    }

    return $args;
});

// Very important to allow to test headers
ob_start();