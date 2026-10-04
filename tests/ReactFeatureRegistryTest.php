<?php

/** Standalone workspace feature test: php tests/ReactFeatureRegistryTest.php */

class AAM_Backend_AccessLevel
{
    private static $instance;
    public $level;

    private function __construct() { $this->level = (object) [ 'type' => 'role' ]; }
    public static function get_instance()
    {
        return self::$instance ?: self::$instance = new self;
    }
    public function get_access_level() { return $this->level; }
}

class TestConfig
{
    public function get($key, $default = null)
    {
        return $key === 'service.hidden' ? false : $default;
    }
}

class AAM
{
    public static function api() { return (object) [ 'config' => new TestConfig ]; }
}

function current_user_can($capability) { return $capability !== 'aam_forbidden'; }
function is_admin() { return false; }
function __($text, $domain = null) { return $text; }
function add_action($name, $callback, $priority = 10) {
    $GLOBALS['test_actions'][$name][] = $callback;
}
function do_action($name, ...$args) {
    foreach ($GLOBALS['test_actions'][$name] ?? [] as $callback) {
        $callback(...$args);
    }
}
class AAM_Restful_AdminToolbar {
    public static function bootstrap() {}
}
class AAM_Framework_Type_AccessLevel {
    const ROLE = 'role';
    const USER = 'user';
    const ALL = 'default';
}

class LegacyView
{
    public function __construct() { throw new RuntimeException('A view was instantiated'); }
}

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

require __DIR__ . '/../application/Backend/Feature.php';

verify(AAM_Backend_Feature::registerFeature((object) [
    'uid' => 'abilities', 'type' => 'main', 'title' => 'Abilities',
    'position' => 20, 'view' => LegacyView::class
]), 'A workspace feature must register without constructing a view');
verify(AAM_Backend_Feature::registerFeature((object) [
    'uid' => 'menu', 'type' => 'main', 'title' => 'Menu',
    'position' => 10, 'view' => LegacyView::class
]), 'A second feature must register');
AAM_Backend_Feature::registerFeature((object) [
    'uid' => 'hidden', 'type' => 'main', 'option' => 'service.hidden',
    'view' => LegacyView::class
]);
AAM_Backend_Feature::registerFeature((object) [
    'uid' => 'forbidden', 'type' => 'main', 'capability' => 'aam_forbidden',
    'view' => LegacyView::class
]);

$features = AAM_Backend_Feature::retrieveList('main');
verify(array_column($features, 'uid') === [ 'menu', 'abilities' ],
    'The workspace must retain feature order and visibility');

require __DIR__ . '/../application/Service/BaseTrait.php';
require __DIR__ . '/../application/Backend/Feature/Abstract.php';
require __DIR__ . '/../application/Backend/Feature/Main/Welcome.php';
require __DIR__ . '/../application/Backend/Feature/Main/AdminToolbar.php';
require __DIR__ . '/../application/Service/Welcome.php';
require __DIR__ . '/../application/Service/AdminToolbar.php';
require __DIR__ . '/../application/Backend/View.php';

AAM_Service_Welcome::bootstrap();
AAM_Service_AdminToolbar::bootstrap();
do_action('init');
AAM_Backend_View::get_instance();

$features = array_column(AAM_Backend_Feature::retrieveList('main'), 'uid');
verify(in_array('welcome', $features, true) && in_array('toolbar', $features, true),
    'REST preload must receive available services when is_admin() is false');

echo "React feature registry passed\n";
