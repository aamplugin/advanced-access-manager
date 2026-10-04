<?php

/**
 * Standalone contract test: php tests/ApplicationPasswordsServiceTest.php
 */

$GLOBALS['hooks'] = [];
$GLOBALS['has_capability'] = true;
$GLOBALS['allowed_user_ids'] = [7];
$GLOBALS['can_list'] = false;

function add_filter($name, $callback, $priority = 10, $accepted_args = 1)
{
    $GLOBALS['hooks'][$name] = $callback;
}
function add_action($name, $callback, $priority = 10, $accepted_args = 1)
{
    $GLOBALS['hooks'][$name] = $callback;
}
function is_admin() { return true; }
function user_can($user, $capability)
{
    $id = is_object($user) ? $user->ID : $user;
    return in_array($id, $GLOBALS['allowed_user_ids'], true);
}
function current_user_can($capability, $user_id = null) { return $GLOBALS['can_list']; }
function __($text, $domain = null) { return $text; }
function esc_html__($text, $domain = null) { return $text; }
function esc_html_e($text, $domain = null) { echo htmlspecialchars($text, ENT_QUOTES); }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES); }
function _n($single, $plural, $number, $domain = null) { return $number === 1 ? $single : $plural; }
function number_format_i18n($number) { return (string) $number; }
function human_time_diff($from, $to) { return '1 hour'; }
function wp_date($format, $timestamp) { return 'Sep 25, 2026'; }
function get_edit_user_link($id) { return '/wp-admin/user-edit.php?user_id=' . $id; }
function wp_is_application_passwords_available_for_user($id) { return true; }
function absint($number) { return abs((int) $number); }
function sanitize_key($text) { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $text)); }
function wp_unslash($text) { return stripslashes($text); }

class WP_Application_Passwords
{
    const USERMETA_KEY_APPLICATION_PASSWORDS = '_application_passwords';
    public static $passwords = [];
    public static function get_user_application_passwords($user_id) { return self::$passwords; }
}
class TestCaps
{
    public function exists($capability) { return $GLOBALS['has_capability']; }
}
class TestApi
{
    public $caps;
    public function __construct() { $this->caps = new TestCaps(); }
}
class AAM
{
    public static function api() { return new TestApi(); }
}

require __DIR__ . '/../application/Service/BaseTrait.php';
require __DIR__ . '/../application/Service/ApplicationPasswords.php';

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$service = AAM_Service_ApplicationPasswords::bootstrap();
verify(isset($GLOBALS['hooks']['manage_users_columns']), 'Users column hook missing');
verify(isset($GLOBALS['hooks']['users_list_table_query_args']), 'Users filter hook missing');
verify($service->is_available_for_user(true, (object) ['ID' => 7]), 'Allowed user should retain access');
verify(!$service->is_available_for_user(true, (object) ['ID' => 8]), 'Denied user should lose access');
verify(!$service->is_available_for_user(false, (object) ['ID' => 7]), 'Earlier denial must remain denied');

$caps = $service->map_meta_cap(['edit_users'], 'create_app_password', 8);
verify(in_array('do_not_allow', $caps, true), 'Application password meta cap must be blocked');
verify($service->map_meta_cap(['edit_users'], 'list_app_passwords', 7) === ['edit_users'], 'Allowed user must retain primitive caps');
verify($service->map_meta_cap(['edit_users'], 'edit_user', 8) === ['edit_users'], 'Other meta caps must be unaffected');

$GLOBALS['has_capability'] = false;
verify($service->map_meta_cap(['edit_users'], 'delete_app_password', 8) === ['edit_users'], 'Absent AAM cap must not change WordPress access');
$GLOBALS['has_capability'] = true;

$columns = $service->add_users_column(['username' => 'Username', 'posts' => 'Posts']);
verify(array_keys($columns) === ['username', AAM_Service_ApplicationPasswords::COLUMN, 'posts'], 'Column must precede Posts');
verify($service->render_users_column('unchanged', 'username', 8) === 'unchanged', 'Other columns must remain unchanged');
verify($service->render_users_column('', AAM_Service_ApplicationPasswords::COLUMN, 8) === '&mdash;', 'Unauthorized password details must stay hidden');

$GLOBALS['can_list'] = true;
WP_Application_Passwords::$passwords = [
    ['name' => 'Mobile <App>', 'last_used' => time() - 3600, 'created' => time() - 7200]
];
$html = $service->render_users_column('', AAM_Service_ApplicationPasswords::COLUMN, 7);
verify(strpos($html, 'Mobile &lt;App&gt;') !== false, 'Password name must render and be escaped');
verify(strpos($html, 'application-passwords-section') !== false, 'Popover must link to WordPress password management');
verify(strpos($html, 'aria-expanded="false"') !== false, 'Popover trigger must expose its state');
WP_Application_Passwords::$passwords = [];
verify(strpos($service->render_users_column('', AAM_Service_ApplicationPasswords::COLUMN, 7), 'No passwords') !== false, 'Empty state missing');

$_GET['aam_ap_filter'] = 'has';
$args = $service->filter_users_query(['meta_query' => [['key' => 'department', 'value' => 'sales']]]);
verify($args['meta_query']['relation'] === 'AND', 'Existing user query must be preserved');
verify($args['meta_query'][1]['key'] === '_application_passwords', 'Filter must target application password meta');
verify($args['meta_query'][1]['value'] === [serialize([]), ''], 'Empty password lists must be excluded');

$_GET['aam_ap_filter'] = '';
verify($service->filter_users_query(['number' => 20]) === ['number' => 20], 'Unfiltered query must remain unchanged');

echo "Application passwords service contract passed\n";
