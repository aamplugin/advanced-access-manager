<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API gateway
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
final class AAM_Core_Gateway {
    
    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Gateway
     * 
     * @access protected 
     */
    protected static $instance = null;
    
    /**
     * Constructor
     */
    protected function __construct() {}
    
    /**
     * Get AAM configuration option
     * 
     * @param string $option
     * @param mixed  $default
     * 
     * @return mixed
     * 
     * @access public
     */
    public function getConfig($option, $default = null) {
        return AAM_Core_Config::get($option, $default);
    }
    
    /**
     * Get user
     * 
     * If no $id specified, current user will be returned
     * 
     * @param int $id Optional user id
     * 
     * @return AAM_Core_Subject
     * 
     * @access public
     */
    public function getUser($id = null) {
        if (!empty($id)) {
            $user = new AAM_Core_Subject_User($id);
        } elseif (get_current_user_id()) {
            $user = AAM::getUser();
        } else {
            $user = new AAM_Core_Subject_Visitor();
        }
        
        return $user;
    }
    
    /**
     * Log any critical message
     * 
     * @param string $message
     * @param string $markers...
     * 
     * @access public
     */
    public function log() {
        call_user_func_array('AAM_Core_Console::add', func_get_args());
    }
    
    /**
     * Deny access for current HTTP request
     * 
     * @param mixed $params
     * 
     * @return void
     * 
     * @access public
     */
    public function denyAccess($params = null) {
        AAM_Core_API::reject(AAM_Core_Api_Area::get(), $params);
    }
    
    /**
     * Check if capability exists
     * 
     * This method checks if provided capability exists (registered for any role).
     * 
     * @param string $capability
     * 
     * @return boolean
     * 
     * @access public
     */
    public function capabilityExists($capability) {
        return AAM_Core_API::capabilityExists($capability);
    }
    
    /**
     * Get instance of the API gateway
     * 
     * @return AAM_Core_Gateway
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
}