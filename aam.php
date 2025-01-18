<?php

/**
 * Plugin Name: Advanced Access Manager
 * Description: Powerfully robust WordPress plugin designed to help you control every aspect of your website, your way.
 * Version: 6.9.45
 * Author: AAM <support@aamplugin.com>
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
 * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/407
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/325
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/300
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/286
 * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/282
 * @since 6.9.4  https://github.com/aamplugin/advanced-access-manager/issues/238
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @author AAM <support@aamplugin.com>
 *
 * @version 6.9.36
 */
class AAM
{

    /**
     * Single instance of itself
     *
     * @var AAM
     *
     * @access private
     * @version 6.0.0
     */
    private static $_instance = null;

    /**
     * User Subject
     *
     * @var AAM_Core_Subject_User|AAM_Core_Subject_Visitor
     *
     * @access private
     * @version 6.0.0
     */
    private $_user = null;

    /**
     * Initialize the AAM Object
     *
     * @return void
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/407
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/286
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.36
     */
    protected function __construct() { }

    /**
     * Set Current User
     *
     * @param AAM_Core_Subject $user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setUser(AAM_Core_Subject $user)
    {
        $this->_user = $user;
    }

    /**
     * Get AAM API manager
     *
     * @return AAM_Core_Gateway
     *
     * @access public
     * @version 6.0.0
     */
    public static function api()
    {
        return AAM_Core_Gateway::getInstance();
    }

    /**
     * Get current user
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public static function getUser()
    {
        return self::getInstance()->_user;
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
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.4
     */
    public static function onPluginsLoaded()
    {
        // Load the core service first
        require_once __DIR__ . '/application/Service/Core.php';

        // Load all the defined AAM services
        foreach (new DirectoryIterator(__DIR__ . '/application/Service') as $service) {
            if ($service->isFile()) {
                require_once $service->getPathname();
            }
        }

        do_action('aam_services_loaded');

        // Load AAM
        AAM::getInstance();
    }

    /**
     * Hook on WP core init
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function onInit()
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
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/407
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.36
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Init current user
            self::$_instance->_initialize_current_user();

            // Load AAM internationalization
            load_plugin_textdomain(AAM_KEY, false, 'advanced-access-manager/lang');

            // Validate logged in user status
            AAM_Service_Core::getInstance()->verify_user_status();
        }

        return self::$_instance;
    }

    /**
     * Initialize current user
     *
     * @return void
     *
     * @access private
     * @version 6.9.36
     */
    private function _initialize_current_user()
    {
        // Initialize current user
        $this->_init_user();

        // Make sure if user is changed dynamically, AAM adjusts accordingly
        add_action('set_current_user', function() {
            $this->_init_user();
        });

        // The same with with after user login. WordPress core has bug with this
        add_action('wp_login', function($_, $user) {
            $this->_init_user($user);
        }, 10, 2);
    }

    /**
     * Change current user
     *
     * This method is triggered if some process updates current user
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.9.36
     */
    private function _init_user($user = null)
    {
        global $current_user;

        // Important! Do not use WP core function to avoid loop
        if (is_a($user, 'WP_User')) {
            $id = $user->ID;
        } else {
            $id = (is_a($current_user, 'WP_User') ? $current_user->ID : null);
        }

        // Change current user
        if ($id) {
            $user = new AAM_Core_Subject_User($id);
        } else {
            $user = new AAM_Core_Subject_Visitor();
        }

        $this->setUser($user);

        $user->initialize();

        return $user;
    }

    /**
     * Activation hook
     *
     * @return void
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/325
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.17
     */
    public static function activate()
    {
        global $wp_version;

        //check PHP Version
        if (version_compare(PHP_VERSION, '5.6.40') === -1) {
            exit(__('PHP 5.6.40 or higher is required.', AAM_KEY));
        } elseif (version_compare($wp_version, '5.0.0') === -1) {
            exit(__('WP 5.0.0 or higher is required.', AAM_KEY));
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
     * @version 6.9.11
     */
    public static function afterActivation($plugin)
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
        //trigger any uninstall hook that is registered by any extension
        do_action('aam-uninstall-action');

        //clear all AAM settings
        AAM_Core_API::clearSettings();
    }

}

if (defined('ABSPATH')) {
    // Define few common constants
    define('AAM_MEDIA', plugins_url('/media', __FILE__));
    define('AAM_KEY', 'advanced-access-manager');
    define('AAM_VERSION', '6.9.45');
    define('AAM_BASEDIR', __DIR__);

    // Load vendor
    require __DIR__ . '/vendor/autoload.php';

    // Register autoloader
    require(__DIR__ . '/autoloader.php');
    AAM_Autoloader::register();

    // Keep this as the lowest priority
    add_action('plugins_loaded', 'AAM::onPluginsLoaded', -999);

    // The highest priority (higher the core)
    // this is important to have to catch events like register core post types
    add_action('init', 'AAM::onInit', -1);

    // Activation & deactivation hooks
    register_activation_hook(__FILE__, array('AAM', 'activate'));
    register_uninstall_hook(__FILE__, array('AAM', 'uninstall'));

    // Improve user experience by redirecting user to the AAM page after it is
    // activated
    add_action('activated_plugin', array('AAM', 'afterActivation'));
}