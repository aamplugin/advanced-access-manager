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
     * Check if current user has access to specified resource
     * 
     * Apply all access/security policies and identify if user has access to specified
     * resource.
     * 
     * @param string $resource
     * @param string $action
     * 
     * @return mixed Boolean true|false if explicit access is defined or null if no
     *               exact match found
     */
    public function isAllowed($resource, $action = null) {
        $policy = AAM::api()->getUser()->getObject('policy');
        
        return $policy->isAllowed($resource, $action);
    }
    
    /**
     * Check if feature is enabled
     * 
     * @param string $feature
     * @param string $plugin
     * 
     * @return boolean|null
     * 
     * @access public
     * @since  v5.7.3
     */
    public function isEnabled($feature, $plugin = 'advanced-access-manager') {
        $policy = AAM::api()->getUser()->getObject('policy');
        
        return $policy->isEnabled($feature, $plugin);
    }
    
    /**
     * Get policy manager
     * 
     * @return AAM_Core_Policy_Manager
     * 
     * @access public
     */
    public function getPolicyManager() {
        return AAM_Core_Policy_Manager::getInstance();
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