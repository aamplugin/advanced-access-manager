<?php

/**
 * Plugin Name: Advanced Access Manager
 * Description: All you need to manage access to your WordPress website
 * Version: 5.9.3
 * Author: Vasyl Martyniuk <vasyl@vasyltech.com>
 * Author URI: https://vasyltech.com
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
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM {

    /**
     * Single instance of itself
     *
     * @var AAM
     *
     * @access private
     */
    private static $_instance = null;

    /**
     * User Subject
     *
     * @var AAM_Core_Subject_User|AAM_Core_Subject_Visitor
     *
     * @access private
     */
    private $_user = null;

    /**
     * Initialize the AAM Object
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct() {
        //initialize current subject
        if (is_user_logged_in()) {
            $this->setUser(new AAM_Core_Subject_User(get_current_user_id()));
        } else {
            $this->setUser(new AAM_Core_Subject_Visitor(''));
        }
    }

    /**
     * Set Current User
     *
     * @param AAM_Core_Subject $user
     *
     * @return void
     *
     * @access public
     */
    protected function setUser(AAM_Core_Subject $user) {
        $this->_user = $user;
    }
    
    /**
     * 
     * @return type
     */
    public static function api() {
        return AAM_Core_Gateway::getInstance();
    }

    /**
     * Get current user
     * 
     * @return AAM_Core_Subject
     * 
     * @access public
     */
    public static function getUser() {
        return self::getInstance()->_user;
    }

    /**
     * Make sure that AAM UI Page is used
     *
     * @return boolean
     *
     * @access public
     */
    public static function isAAM() {
        $page      = AAM_Core_Request::get('page');
        $action    = AAM_Core_Request::post('action');
        
        $intersect = array_intersect(array('aam', 'aamc'), array($page, $action));
        
        return (is_admin() && count($intersect));
    }
    
    /**
     * Bootstrap AAM
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function onPluginsLoaded() {
        //load AAM core config
        AAM_Core_Config::bootstrap();
        
        //login control
        if (AAM_Core_Config::get('core.settings.secureLogin', true)) {
            AAM_Core_Login::bootstrap();
        }

        //JWT Authentication
        if (AAM_Core_Config::get('core.settings.jwtAuthentication', true)) {
            AAM_Core_Jwt_Manager::bootstrap();
        }
        
        // Load AAM
        AAM::getInstance();
        
        //load all installed extension
        if (AAM_Core_Config::get('core.settings.extensionSupport', true)) {
            AAM_Extension_Repository::getInstance()->load();
        }
        
        //load WP Core hooks
        AAM_Shared_Manager::bootstrap();
    }
    
    /**
     * Hook on WP core init
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function onInit() {
        //bootstrap the correct interface
        if (AAM_Core_Api_Area::isBackend()) {
            AAM_Backend_Manager::bootstrap();
        } elseif (AAM_Core_Api_Area::isFrontend()) {
            AAM_Frontend_Manager::bootstrap();
        }
    }

    /**
     * Initialize the AAM plugin
     *
     * @return AAM
     *
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Get current user
            $user = self::$_instance->getUser();
            
            // Load user capabilities
            $user->initialize();
            
            // Logout user if he/she is blocked
            $status = $user->getUserStatus();
            
            // If user is not active, then perform rollback on user
            if ($status['status'] !== 'active') {
                $user->restrainUserAccount($status);
            }
            
            load_plugin_textdomain(AAM_KEY, false, 'advanced-access-manager/Lang');
        }

        return self::$_instance;
    }

    /**
     * Run daily routine
     * 
     * Check server extension versions
     * 
     * @return void
     * 
     * @access public
     */
    public static function cron() {
        $extensions = AAM_Core_API::getOption('aam-extensions', null, 'site');
        
        if (!empty($extensions) && AAM_Core_Config::get('core.settings.cron', true)) {
            //grab the server extension list
            AAM_Core_API::updateOption(
                    'aam-check', AAM_Core_Server::check(), 'site'
            );
        }
    }

    /**
     * Create aam folder
     * 
     * @return void
     * 
     * @access public
     */
    public static function activate() {
        global $wp_version;
        
        //check PHP Version
        if (version_compare(PHP_VERSION, '5.3.0') === -1) {
            exit(__('PHP 5.3.0 or higher is required.', AAM_KEY));
        } elseif (version_compare($wp_version, '4.0') === -1) {
            exit(__('WP 4.0 or higher is required.', AAM_KEY));
        }
    }

    /**
     * De-install hook
     *
     * Remove all leftovers from AAM execution
     *
     * @return void
     *
     * @access public
     */
    public static function uninstall() {
        //trigger any uninstall hook that is registered by any extension
        do_action('aam-uninstall-action');

        //remove aam directory if exists
        $dirname = WP_CONTENT_DIR . '/aam';
        if (file_exists($dirname)) {
            AAM_Core_API::removeDirectory($dirname);
        }
        
        //clear all AAM settings
        AAM_Core_API::clearSettings();
        
        //clear schedules
        wp_clear_scheduled_hook('aam-cron');
    }

}

if (defined('ABSPATH')) {
    //define few common constants
    define(
        'AAM_MEDIA', 
        preg_replace('/^http[s]?:/', '', plugins_url('/media', __FILE__))
    );
    define('AAM_KEY', 'advanced-access-manager');
    define('AAM_EXTENSION_BASE', WP_CONTENT_DIR . '/aam/extension');
    define('AAM_BASEDIR', dirname(__FILE__));
    
    //load vendor
    require AAM_BASEDIR . '/vendor/autoload.php';
    
    //register autoloader
    require (dirname(__FILE__) . '/autoloader.php');
    AAM_Autoloader::register();
    
    add_action('plugins_loaded', 'AAM::onPluginsLoaded', 1);
    
    //the highest priority (higher the core)
    //this is important to have to catch events like register core post types
    add_action('init', 'AAM::onInit', -1);
    
    //register API manager is applicable
    add_action('parse_request', 'AAM_Api_Manager::bootstrap', 1);
    
    //schedule cron
    if (!wp_next_scheduled('aam-cron')) {
        wp_schedule_event(time(), 'daily', 'aam-cron');
    }
    add_action('aam-cron', 'AAM::cron');

    //activation & deactivation hooks
    register_activation_hook(__FILE__, array('AAM', 'activate'));
    register_uninstall_hook(__FILE__, array('AAM', 'uninstall'));
}