<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM frontend filter
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Frontend_Filter {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Frontend_Filter
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //bootstrap authorization layer
        AAM_Frontend_Authorization::bootstrap();
        
        //manage access to frontend posts & pages
        add_action('wp', array($this, 'wp'), 999);
        add_action('404_template', array($this, 'themeRedirect'), 999);
        
        //important to keep this option optional for optimization reasons
        if (AAM_Core_Config::get('core.settings.checkPostVisibility', true)) {
            //filter navigation pages & taxonomies
            add_filter('wp_get_nav_menu_items', array($this, 'getNavigationMenu'), 999);
        }
        
        //widget filters
        add_filter('sidebars_widgets', array($this, 'filterWidgets'), 999);
        
        //get control over commenting stuff
        add_filter('comments_open', array($this, 'commentOpen'), 10, 2);
    }
    
    /**
     * Main frontend access control hook
     *
     * @return void
     *
     * @access public
     * @global WP_Post $post
     */
    public function wp() {
        global $wp_query;
        
        if ($wp_query->is_404) { // Handle 404 redirect
            $type = AAM_Core_Config::get('frontend.404redirect.type', 'default');
            do_action('aam-access-rejected-action', 'frontend', array(
                'hook' => 'aam_404', 
                'uri'  => AAM_Core_Request::server('REQUEST_URI')
            ));
            
            if ($type != 'default') {
                AAM_Core_API::redirect(
                    AAM_Core_Config::get("frontend.404redirect.{$type}")
                );
            }
        } elseif ($wp_query->is_single || $wp_query->is_page 
                                || $wp_query->is_posts_page || $wp_query->is_home) {
            $post = AAM_Core_API::getCurrentPost();
            
            if ($post) {
                AAM_Frontend_Authorization::getInstance()->chechReadAuth($post);
            }
        }
    }
    
    /**
     * Theme redirect
     * 
     * Super important function that cover the 404 redirect that triggered by theme
     * when page is not found. This covers the scenario when page is restricted from
     * listing and read.
     * 
     * @global type $wp_query
     * 
     * @param type $template
     * 
     * @return string
     * 
     * @access public
     */
    public function themeRedirect($template) {
        $post = AAM_Core_API::getCurrentPost();
        
        if ($post) {
            AAM_Frontend_Authorization::getInstance()->chechReadAuth($post);
        }
        
        return $template;
    }
    
    /**
     * Filter Navigation menu
     *
     * @param array $pages
     *
     * @return array
     *
     * @access public
     */
    public function getNavigationMenu($pages) {
        if (is_array($pages)) {
            foreach ($pages as $i => $page) {
                if (in_array($page->type, array('post_type', 'custom'))) {
                    $object = AAM::getUser()->getObject('post', $page->object_id);
                    $hidden = $object->get('frontend.hidden');
                    $list   = $object->get('frontend.list');
                    
                    if ($hidden || $list) {
                        unset($pages[$i]);
                    }
                }
            }
        }

        return $pages;
    }
    
    /**
     * Filter frontend widgets
     *
     * @param array $widgets
     *
     * @return array
     *
     * @access public
     */
    public function filterWidgets($widgets) {
        return AAM::getUser()->getObject('metabox')->filterFrontend($widgets);
    }
    
    /**
     * Control frontend commenting feature
     *
     * @param boolean $open
     * @param int     $post_id
     *
     * @return boolean
     *
     * @access public
     */
    public function commentOpen($open, $post_id) {
        $object = AAM::getUser()->getObject('post', $post_id);
        
        return ($object->has('frontend.comment') ? false : $open);
    }
    
    /**
     * Register backend filters and actions
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
    }

}