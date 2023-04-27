<?php

/**
 * Plugin Name: Advanced Access Manager
 * Description: Collection of features to manage your WordPress website authentication, authorization and monitoring
 * Version: 6.9.10
 * Author: Vasyl Martyniuk <vasyl@vasyltech.com>
 * Author URI: https://vasyltech.com
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
 * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.9.4
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
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        // Initialize current user
        $this->initializeUser();

        // Make sure if user is changed dynamically, AAM adjusts accordingly
        add_action('set_current_user', function() {
            $this->initializeUser();
        });
    }

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
     * Change current user
     *
     * This method is triggered if some process updates current user
     *
     * @return AAM_Core_Subject
     *
     * @access public
     * @version 6.0.0
     */
    public function initializeUser()
    {
        global $current_user;

        // Important! Do not use WP core function to avoid loop
        $id = (is_a($current_user, 'WP_User') ? $current_user->ID : null);

        // Change current user
        if ($id) {
            $user = (new AAM_Core_Subject_User($id))->initialize();
        } else {
            $user = new AAM_Core_Subject_Visitor();
        }

        $this->setUser($user);

        return $user;
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
        // Load AAM core config
        AAM_Core_Config::bootstrap();

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
     * @access public
     * @version 6.0.0
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Load AAM internationalization
            load_plugin_textdomain(AAM_KEY, false, 'advanced-access-manager/lang');

            // Validate logged in user status
            if (is_user_logged_in()) {
                AAM::getUser()->validateStatus();
            }
        }

        return self::$_instance;
    }

    /**
     * Activation hook
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function activate()
    {
        global $wp_version;

        //check PHP Version
        if (version_compare(PHP_VERSION, '5.6.40') === -1) {
            exit(__('PHP 5.6.40 or higher is required.', AAM_KEY));
        } elseif (version_compare($wp_version, '4.7.0') === -1) {
            exit(__('WP 4.7.0 or higher is required.', AAM_KEY));
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
    define('AAM_VERSION', '6.9.10');
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
}