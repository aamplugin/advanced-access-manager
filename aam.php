<?php

/**
 * Plugin Name: Advanced Access Manager
 * Description: Powerfully robust WordPress plugin designed to help you control every aspect of your website, your way.
 * Version: 7.0.3
 * Author: VasylTech LLC <support@aamplugin.com>
 * Author URI: https://aamportal.com
 * Text Domain: advanced-access-manager
 * Domain Path: /lang/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.html
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
        AAM_Service_Core::class                 => '__return_true',
        AAM_Service_Urls::class                 => 'service.urls.enabled',
        AAM_Service_LoginRedirect::class        => 'service.login_redirect.enabled',
        AAM_Service_LogoutRedirect::class       => 'service.logout_redirect.enabled',
        AAM_Service_AccessDeniedRedirect::class => 'service.access_denied_redirect.enabled',
        AAM_Service_NotFoundRedirect::class     => 'service.not_found_redirect.enabled',
        AAM_Service_BackendMenu::class          => 'service.backend_menu.enabled',
        AAM_Service_Metaboxes::class            => 'service.metaboxes.enabled',
        AAM_Service_Widgets::class              => 'service.widgets.enabled',
        AAM_Service_AdminToolbar::class         => 'service.admin_toolbar.enabled',
        AAM_Service_ApiRoute::class             => 'service.api_route.enabled',
        AAM_Service_Identity::class             => 'service.identity.enabled',
        AAM_Service_Content::class              => 'service.content.enabled',
        AAM_Service_SecureLogin::class          => 'service.secure_login.enabled',
        AAM_Service_Jwt::class                  => 'service.jwt.enabled',
        AAM_Service_Capability::class           => 'service.capability.enabled',
        AAM_Service_SecurityAudit::class        => 'service.security_audit.enabled',
        AAM_Service_Welcome::class              => 'service.welcome.enabled',
        AAM_Service_Policies::class             => 'service.policies.enabled',
        AAM_Service_Hooks::class                => 'service.hooks.enabled',
        AAM_Service_Shortcodes::class           => 'service.shortcodes.enabled'
    ];

    /**
     * Single instance of itself
     *
     * @var AAM
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Current user
     *
     * @var AAM_Framework_AccessLevel_Interface
     * @access private
     *
     * @version 7.0.0
     */
    private $_current_user = null;

    /**
     * Initialize the AAM Object
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct() { }

    /**
     * Get AAM API manager
     *
     * @return AAM_Core_Gateway
     * @access public
     *
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
     * @access public
     *
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
     * @access private
     *
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
        AAM::api()->setup($this->_current_user);
    }

    /**
     * Bootstrap AAM when all plugins are loaded
     *
     * @return void
     * @access public
     *
     * @version 7.0.2
     */
    public static function on_plugins_loaded()
    {
        // Load AAM
        self::get_instance();

        // Load all the defined AAM services
        foreach(self::SERVICES as $service_class => $flag) {
            if ($flag === '__return_true' || AAM::api()->config->get($flag, true)) {
                call_user_func("{$service_class}::bootstrap");
            }
        }
    }

    /**
     * Hook on WP core init
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function on_init()
    {
        if (is_admin()) {
            AAM_Backend_Manager::bootstrap();
        }

        // Load AAM internationalization
        load_plugin_textdomain(
            'advanced-access-manager',
            false,
            'advanced-access-manager/lang'
        );
    }

    /**
     * Initialize the AAM plugin
     *
     * @return AAM
     * @access public
     *
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

            // Initialize current user
            self::$_instance->_init_current_user();
        }

        return self::$_instance;
    }

    /**
     * Activation hook
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function activate()
    {
        global $wp_version;

        // Check PHP Version
        if (version_compare(PHP_VERSION, '5.6.40') === -1) {
            exit(__('PHP 5.6.40 or higher is required.', 'advanced-access-manager'));
        } elseif (version_compare($wp_version, '5.8.0') === -1) {
            exit(__('WP 5.8.0 or higher is required.', 'advanced-access-manager'));
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
     *
     * @version 7.0.0
     */
    public static function activated_plugin($plugin)
    {
        $misc = AAM::api()->misc;

        if (
            $plugin === "advanced-access-manager/aam.php"
            && !is_network_admin()
            && $misc->get($_SERVER, 'REQUEST_METHOD') === 'GET'
            && filter_input(INPUT_GET, 'action') === 'activate'
            && $misc->get($_SERVER, 'SCRIPT_NAME') === '/wp-admin/plugins.php'
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
     * @access public
     *
     * @version 7.0.0
     */
    public static function uninstall()
    {
        AAM_Service_Core::get_instance()->reset();

        // Trigger any uninstall hook that is registered by any extension
        do_action('aam_uninstall_action');
    }

}

if (defined('ABSPATH')) {
    // Define few common constants
    define('AAM_MEDIA', plugins_url('/media', __FILE__));
    define('AAM_KEY', 'advanced-access-manager');
    define('AAM_VERSION', '7.0.3');
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