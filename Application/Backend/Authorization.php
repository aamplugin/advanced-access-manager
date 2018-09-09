<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend authorization
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Authorization {

    /**
     * Instance of itself
     * 
     * @var AAM_Backend_Authorization
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
        //control admin area
        if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
            add_action('admin_init', array($this, 'checkScreenAccess'));
        }
    }
    
    /**
     * Check screen access
     * 
     * @return void
     * 
     * @access public
     * @global string $plugin_page
     */
    public function checkScreenAccess() {
        global $plugin_page;
        
        //compile menu
        $menu = $plugin_page;
        
        if (empty($menu)){
            $menu     = basename(AAM_Core_Request::server('SCRIPT_NAME'));
            $taxonomy = AAM_Core_Request::get('taxonomy');
            $postType = AAM_Core_Request::get('post_type');
            $page     = AAM_Core_Request::get('page');
            
            if (!empty($taxonomy)) {
                $menu .= '?taxonomy=' . $taxonomy;
            } elseif (!empty($postType) && ($postType !== 'post')) {
                $menu .= '?post_type=' . $postType;
            } elseif (!empty($page)) {
                $menu .= '?page=' . $page;
            }
        }
        
        if (AAM::getUser()->getObject('menu')->has($menu, true)) {
            AAM_Core_API::reject(
                'backend', array('hook' => 'access_backend_menu', 'id' => $menu)
            );
        }
    }
    
    /**
     * Alias for the bootstrap
     * 
     * @return AAM_Backend_Authorization
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        return self::bootstrap();
    }
    
    /**
     * Bootstrap authorization layer
     * 
     * @return AAM_Backend_Authorization
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
}