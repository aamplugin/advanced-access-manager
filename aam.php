<?php

/**
 * Plugin Name: Advanced Access Manager
 * Description: Powerfully robust WordPress plugin designed to help you control every aspect of your website, your way.
 * Version: 7.0.0-alpha.6
 * Author: VasylTech LLC <support@aamplugin.com>
 * Author URI: https://aamportal.com
 * Text Domain: advanced-access-manager
 * Domain Path: /lang/
 *
 * -------
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'license.txt', which is part of Advanced Access Manager source package.
 *
 **/

/**
 * Main plugin's class
 *
 * @package AAM
 * @author AAM <support@aamplugin.com>
 *
 * @version 7.0.0
 */
class AAM
{

    /**
     * Collection of AAM services
     *
     * @version 7.0.0
     */
    const SERVICES = [
        'AAM_Service_Core',
        'AAM_Service_Urls',
        'AAM_Service_LoginRedirect',
        'AAM_Service_LogoutRedirect',
        'AAM_Service_AccessDeniedRedirect',
        'AAM_Service_NotFoundRedirect',
        'AAM_Service_BackendMenu',
        'AAM_Service_Metaboxes',
        'AAM_Service_Widgets',
        'AAM_Service_AdminToolbar',
        'AAM_Service_ApiRoute',
        'AAM_Service_Identity',
        'AAM_Service_Content',
        'AAM_Service_SecureLogin',
        'AAM_Service_Jwt',
        'AAM_Service_Capability',
        'AAM_Service_SecurityAudit',
        'AAM_Service_Welcome'
        // 'AAM_Service_ExtendedCapabilities' => AAM_Service_ExtendedCapabilities::FEATURE_FLAG,
        // 'AAM_Service_Multisite'            => AAM_Service_Multisite::FEATURE_FLAG,
        // 'AAM_Service_Shortcode'            => AAM_Service_Shortcode::FEATURE_FLAG,

        // 'AAM_Service_AccessPolicy'         => AAM_Service_AccessPolicy::FEATURE_FLAG,
    ];

    /**
     * Single instance of itself
     *
     * @var AAM
     *
     * @access private
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Current user
     *
     * @var AAM_Framework_AccessLevel_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private $_current_user = null;

    /**
     * Initialize the AAM Object
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct() { }

    /**
     * Get AAM API manager
     *
     * @return AAM_Core_Gateway
     *
     * @access public
     * @version 7.0.0
     */
    public static function api()
    {
        return AAM_Core_Gateway::get_instance();
    }

    /**
     * Get current user
     *
     * @return AAM_Framework_AccessLevel_Interface|null
     *
     * @access public
     * @version 7.0.0
     */
    public static function current_user()
    {
        return self::get_instance()->_current_user;
    }

    /**
     * Initialize current user
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _init_current_user($user = null)
    {
        global $current_user;

        // Important! Do not use WP core function to avoid loop
        if (is_a($user, 'WP_User')) {
            $id = $user->ID;
        } else {
            $id = (is_a($current_user, 'WP_User') ? $current_user->ID : null);
        }

        // Set current user
        if (is_numeric($id) && $id > 0) {
            $this->_current_user = self::api()->user($id);
        } else {
            $this->_current_user = self::api()->visitor();
        }

        // Updating AAM Framework default context. This way we do not have to write
        // code like AAM::api()->user()->...
        AAM::api()->setup([
            'access_level' => $this->_current_user
        ]);
    }

    /**
     * Make sure that AAM UI Page is used
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function isAAM()
    {
        $page   = filter_input(INPUT_GET, 'page');
        $action = filter_input(INPUT_POST, 'action');

        $intersect = array_intersect(array('aam', 'aamc'), array($page, $action));

        return (is_admin() && count($intersect));
    }

    /**
     * Bootstrap AAM when all plugins are loaded
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public static function on_plugins_loaded()
    {
        // Load all the defined AAM services
        foreach(self::SERVICES as $service_class) {
            call_user_func("{$service_class}::bootstrap");
        }

        // Load AAM
        self::get_instance();
    }

    /**
     * Hook on WP core init
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public static function on_init()
    {
        if (is_admin()) {
            AAM_Backend_Manager::bootstrap();
        }
    }

    /**
     * Initialize the AAM plugin
     *
     * @return AAM
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Make sure if user is changed dynamically, AAM adjusts accordingly
            add_action('set_current_user', function() {
                self::$_instance->_current_user = null;

                // Reinitialize current user
                self::$_instance->_init_current_user();
            }, 1);

            // The same with with after user login. WordPress core has bug with this
            add_action('wp_login', function($_, $user) {
                self::$_instance->_init_current_user($user);
            }, 10, 2);

            // Load AAM internationalization
            load_plugin_textdomain(AAM_KEY, false, 'advanced-access-manager/lang');

            // Initialize current user
            self::$_instance->_init_current_user();
        }

        return self::$_instance;
    }

    /**
     * Activation hook
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public static function activate()
    {
        global $wp_version;

        // Check PHP Version
        if (version_compare(PHP_VERSION, '5.6.40') === -1) {
            exit(__('PHP 5.6.40 or higher is required.', AAM_KEY));
        } elseif (version_compare($wp_version, '5.8.0') === -1) {
            exit(__('WP 5.8.0 or higher is required.', AAM_KEY));
        }
    }

    /**
     * Redirect user to AAM page after plugin activation
     *
     * @param string $plugin
     *
     * @return void
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function activated_plugin($plugin)
    {
        if (
            $plugin === "advanced-access-manager/aam.php"
            && !is_network_admin()
            && AAM_Core_Request::server('REQUEST_METHOD') === 'GET'
            && AAM_Core_Request::get('action') === 'activate'
            && AAM_Core_Request::server('SCRIPT_NAME') === '/wp-admin/plugins.php'
        ) {
            wp_redirect(admin_url('admin.php?page=aam')); exit;
        }
    }

    /**
     * Deactivate hook
     *
     * Remove all leftovers from AAM execution
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function uninstall()
    {
        // Trigger any uninstall hook that is registered by any extension
        do_action('aam-uninstall-action');

        //clear all AAM settings
        AAM_Core_API::clearSettings();
    }

}

if (defined('ABSPATH')) {
    // Define few common constants
    define('AAM_MEDIA', plugins_url('/media', __FILE__));
    define('AAM_KEY', 'advanced-access-manager');
    define('AAM_VERSION', '7.0.0-alpha.6');
    define('AAM_BASEDIR', __DIR__);

    // Load vendor
    require __DIR__ . '/vendor/autoload.php';

    // Register autoloader
    require(__DIR__ . '/autoloader.php');
    AAM_Autoloader::register();

    // Keep this as the lowest priority
    add_action('plugins_loaded', 'AAM::on_plugins_loaded', -999);

    // The highest priority (higher the core)
    // this is important to have to catch events like register core post types
    add_action('init', 'AAM::on_init', -1);

    // Activation & deactivation hooks
    register_activation_hook(__FILE__, array('AAM', 'activate'));
    register_uninstall_hook(__FILE__, array('AAM', 'uninstall'));

    // Improve user experience by redirecting user to the AAM page after it is
    // activated
    add_action('activated_plugin', array('AAM', 'activated_plugin'));
}