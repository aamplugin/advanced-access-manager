<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM frontend manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Frontend_Manager {

    /**
     * Instance of itself
     * 
     * @var AAM_Frontend_Manager
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Construct the manager
     * 
     * @return void
     * 
     * @access public
     */
    public function __construct() {
        if (AAM_Core_Config::get('core.settings.frontendAccessControl', true)) {
            AAM_Frontend_Filter::register();
        }
        
        //manage AAM shortcode
        if (AAM_Core_Config::get('core.processShortcodes', true)) {
            add_shortcode('aam', array($this, 'processShortcode'));
        }
        
        //cache clearing hook
        add_action('aam-clear-cache-action', 'AAM_Core_API::clearCache');
        
        //admin bar
        $this->checkAdminBar();
        
        //register login widget
        if (AAM_Core_Config::get('core.settings.secureLogin', true)) {
            add_action('widgets_init', array($this, 'registerLoginWidget'));
            add_action('wp_enqueue_scripts', array($this, 'printJavascript'));
        }
        
        //password protected filter
        add_filter('post_password_required', array($this, 'isPassProtected'), 10, 2);
        //manage password check expiration
        add_filter('post_password_expires', array($this, 'checkPassExpiration'));
    }
    
    /**
     * Process AAM short-codes
     * 
     * @param array  $args
     * @param string $content
     * 
     * @return string
     * 
     * @access public
     */
    public function processShortcode($args, $content) {
        $shortcode = new AAM_Shortcode_Factory($args, $content);
        
        return $shortcode->process();
    }
    
    /**
     * Check admin bar
     * 
     * Make sure that current user can see admin bar
     * 
     * @return void
     * 
     * @access public
     */
    public function checkAdminBar() {
        if (AAM_Core_API::capabilityExists('show_admin_bar')) {
            if (!current_user_can('show_admin_bar')) {
                add_filter('show_admin_bar', '__return_false', PHP_INT_MAX );
            }
        }
    }
    
    /**
     * Register login widget
     * 
     * @return void
     * 
     * @access public
     */
    public function registerLoginWidget() {
        register_widget('AAM_Backend_Widget_Login');
    }
    
    /**
     * Print JS libraries
     *
     * @return void
     *
     * @access public
     */
    public function printJavascript() {
        if (AAM_Core_Config::get('core.settings.secureLogin', true)) {
            wp_enqueue_script(
                'aam-login', 
                AAM_MEDIA . '/js/aam-login.js', 
                array('jquery')
            );

            //add plugin localization
            $locals = array(
                'nonce'   => wp_create_nonce('aam_ajax'),
                'ajaxurl' => admin_url('admin-ajax.php')
            );

            wp_localize_script('aam-login', 'aamLocal', $locals);
        }
    }
    
    /**
     * Check if post is password protected
     * 
     * @param boolean $res
     * @param WP_Post $post
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isPassProtected($res, $post) {
        if (is_a($post, 'WP_Post')) {
            $object = AAM::getUser()->getObject('post', $post->ID);

            if ($object->has('frontend.protected')) {
                require_once( ABSPATH . 'wp-includes/class-phpass.php' );
                $hasher = new PasswordHash( 8, true );
                $pass   = $object->get('frontend.password');
                $hash   = wp_unslash(
                        AAM_Core_Request::cookie('wp-postpass_' . COOKIEHASH)
                );

                $res = empty($hash) ? true : !$hasher->CheckPassword($pass, $hash);
            }
        }
        
        return $res;
    }
    
    /**
     * Get password expiration TTL
     * 
     * @param int $expire
     * 
     * @return int
     * 
     * @access public
     */
    public function checkPassExpiration($expire) {
        $overwrite = AAM_Core_Config::get('feature.post.password.expires', null);
        
        if (!is_null($overwrite)) {
            $expire = ($overwrite ? time() + strtotime($overwrite) : 0);
        }
        
        return $expire;
    }
    
    /**
     * Bootstrap the manager
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
    }

}