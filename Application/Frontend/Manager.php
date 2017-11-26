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
        if (AAM_Core_Config::get('frontend-access-control', true)) {
            AAM_Frontend_Filter::register();
        }
        
        //manage AAM shortcode
        add_shortcode('aam', array($this, 'processShortcode'));
        
        //admin bar
        $this->checkAdminBar();
        
        //register login widget
        if (AAM_Core_Config::get('secure-login', true)) {
            add_action('widgets_init', array($this, 'registerLoginWidget'));
            add_action('wp_enqueue_scripts', array($this, 'printJavascript'));
        }
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
            if (!AAM::getUser()->hasCapability('show_admin_bar')) {
                show_admin_bar(false);
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
        if (AAM_Core_Config::get('secure-login', true)) {
            wp_enqueue_script('aam-login', AAM_MEDIA . '/js/aam-login.js');

            //add plugin localization
            $locals = array(
                'nonce'   => wp_create_nonce('aam_ajax'),
                'ajaxurl' => admin_url('admin-ajax.php')
            );

            wp_localize_script('aam-login', 'aamLocal', $locals);
        }
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