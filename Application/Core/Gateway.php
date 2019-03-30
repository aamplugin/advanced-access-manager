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
     * Prevent from fatal errors
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @return void
     * 
     * @access public
     */
    public function __call($name, $arguments) {
        trigger_error(
            esc_js(__("The method {$name} is not defined in the AAM API", AAM_KEY))
        );
    }
    
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
        $value = AAM_Core_Config::get($option, $default);
        
        return AAM_Core_Compatibility::convertConfig($option, $value);
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
            $user->initialize();
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
     * Get policy manager
     * 
     * @return AAM_Core_Policy_Manager
     * 
     * @access public
     */
    public function getPolicyManager(AAM_Core_Subject $subject = null) {
        return AAM_Core_Policy_Factory::get(
            (is_null($subject) ? $this->getUser() : $subject)
        );
    }
    
    /**
     * Compatibility manager
     *
     * @return AAM_Core_Compatibility
     */
    public function getCompatibilityManager() {
        return AAM_Core_Compatibility::getInstance();
    }

    /**
     * Redirect request
     * 
     * @param string $type
     * @param mixed  $arg
     * 
     * @return void
     * 
     * @access public
     */
    public function redirect($type, $arg = null) {
        $area = AAM_Core_Api_Area::get();
        
        switch($type) {
            case 'login':
                wp_redirect(add_query_arg(
                    array('reason' => 'restricted'), 
                    wp_login_url(AAM_Core_Request::server('REQUEST_URI'))
                ), 307);
                break;
            
            case 'page':
                $page = AAM_Core_API::getCurrentPost();
                if(empty($page) || ($page->ID !== intval($arg))) {
                    wp_safe_redirect(get_page_link($arg), 307);
                }
                break;
                
            case 'message':
                wp_die($arg);
                break;
            
            case 'url':
                if (stripos($arg, AAM_Core_Request::server('REQUEST_URI')) === false) {
                    wp_redirect($arg, 307);
                }
                break;
                
            case 'callback':
                if (is_callable($arg)) {
                    call_user_func($arg);
                }
                break;
                
            default:
                wp_die(AAM_Core_Config::get(
                    "{$area}.access.deny.redirectRule", __('Access Denied', AAM_KEY)
                ));
                break;
        }
        
        exit; // Halt the execution
    }
    
    /**
     * Get current post
     * 
     * @return WP_Post
     * 
     * @access public
     */
    public function getCurrentPost() {
        return AAM_Core_API::getCurrentPost();
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
     * Merge AAM settings
     * 
     * @param array $set1
     * @param array $set2
     * @param string $objectType
     * 
     * @return array
     * 
     * @access public
     */
    public function mergeSettings($set1, $set2, $objectType, $preference = null) {
        $combined = array($set1, $set2);
        $merged   = array();
        
        if (is_null($preference)) {
            $preference = $this->getConfig(
                "core.settings.{$objectType}.merge.preference", 'deny'
            );
        }
        
        // first get the complete list of unique keys
        $keys = array_keys(call_user_func_array('array_merge', $combined));
        
        foreach($keys as $key) {
            foreach($combined as $options) {
                // If merging preference is "deny" and at least one of the access
                // settings is checked, then final merged array will have it set
                // to checked
                if (in_array($preference, array('deny', 'apply'), true) && !empty($options[$key])) {
                    $merged[$key] = $options[$key];
                    break;
                } elseif (in_array($preference, array('allow', 'deprive'), true) && empty($options[$key])) {
                    $merged[$key] = 0;
                    break;
                } elseif (isset($options[$key])) {
                    $merged[$key] = $options[$key];
                }
            }
        }
        
        return $merged;
    } 

    /**
     * Get current AAM version
     *
     * @return string
     * 
     * @access public
     */
    public function getAAMVersion() {
        return AAM_Core_API::version();
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