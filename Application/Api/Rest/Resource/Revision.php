<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM REST Revision Resource
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Api_Rest_Resource_Revision {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Api_Rest_Resource_Revision
     * 
     * @access private 
     */
    private static $_instance = null;
    
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
     * Alias for the bootstrap
     * 
     * @return AAM_Api_Rest_Resource_Revision
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
     * @return AAM_Api_Rest_Resource_Revision
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