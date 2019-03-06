<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM RESTful Users Resource
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Api_Rest_Resource_User {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Api_Rest_Resource_User
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * 
     */
    protected function __construct() {
        add_filter('rest_user_query', array($this, 'userQuery'));
    }
    
    /**
     * Authorize User actions
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_Error|null
     * 
     * @access public
     */
    public function authorize($request) {
        return null;
    }
    
    /**
     * Alter user select query
     * 
     * @param array $args
     * 
     * @return array
     * 
     * @access public
     */
    public function userQuery($args) {
        //current user max level
        $max     = AAM::getUser()->getMaxLevel();
        $exclude = isset($args['role__not_in']) ? $args['role__not_in'] : array();
        $roles   = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            if (AAM_Core_API::maxLevel($role->capabilities) > $max) {
                $exclude[] = $id;
            }
        }
        
        $args['role__not_in'] = $exclude;
        
        return $args;
    }
    
    /**
     * Alias for the bootstrap
     * 
     * @return AAM_Api_Rest_Resource_User
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
     * @return AAM_Api_Rest_Resource_User
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