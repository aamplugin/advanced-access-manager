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
final class AAM_Core_Gateway implements AAM_Core_Contract_Api {
    
    /**
     * User subject slug
     */
    const SUBJECT_USER = 'user';
    
    /**
     * Role subject slug
     */
    const SUBJECT_ROLE = 'role';
    
    /**
     * Default subject slug
     */
    const SUBJECT_DEFAULT = 'default';
    
    /**
     * Visitor subject slug
     */
    const SUBJECT_VISITOR = 'visitor';
    
    /**
     * Cache object slug
     */
    const OBJECT_CACHE = 'cache';
    
    /**
     * Capability object slug
     */
    const OBJECT_CAPABILITY = 'capability';
    
    /**
     * Login Redirect object slug
     */
    const OBJECT_LOGIN_REDIRECT = 'loginRedirect';
    
    /**
     * Logout Redirect object slug
     */
    const OBJECT_LOGOUT_REDIRECT = 'logoutRedirect';
    
    /**
     * Backend Menu object slug
     */
    const OBJECT_BACKEND_MENU = 'menu';
    
    /**
     * Metabox & Widget object slug
     */
    const OBJECT_METABOX_WIDGET = 'metabox';
    
    /**
     * Post object slug
     */
    const OBJECT_POST = 'post';
    
    /**
     * Access Denied Redirect object slug
     */
    const OBJECT_ACCESS_DENIED_REDIRECT = 'redirect';
    
    /**
     * API Route object slug
     */
    const OBJECT_ROUTE = 'route';
    
    /**
     * Hierarchical Term object slug
     */
    const OBJECT_TERM = 'term';
    
    /**
     * Post Type object slug
     */
    const OBJECT_POST_TYPE = 'type';

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
     * @param int $id
     * 
     * @return AAM_Core_Subject_User
     * 
     * @access public
     * @throws Exception If no $id is specified and user is not authenticated
     */
    public function getUserSubject($id = null) {
        if (!empty($id)) {
            if ($id == get_current_user_id()) {
                $user = AAM::getUser();
            } else {
                $user = new AAM_Core_Subject_User($id);
            }
        } elseif (get_current_user_id()) {
            $user = AAM::getUser();
        } else {
            throw new Exception('Current visitor is not authenticated');
        }
        
        return $user;
    }
    
    /**
     * Get role
     * 
     * @param string $id
     * 
     * @return AAM_Core_Subject_Role
     * 
     * @access public
     */
    public function getRoleSubject($id) {
        return new AAM_Core_Subject_Role($id);
    }
    
    /**
     * Get visitor
     * 
     * @return AAM_Core_Subject_Visitor
     * 
     * @access public
     */
    public function getVisitorSubject() {
        return new AAM_Core_Subject_Visitor();
    }
    
    /**
     * Get default subject
     * 
     * @return AAM_Core_Subject_Default
     * 
     * @access public
     */
    public function getDefaultSubject() {
        return new AAM_Core_Subject_Default();
    }
    
    /**
     * Get subject
     * 
     * @param string     $type Subject type (allowed user, role, visitor and default)
     * @param string|int $id   Subject id (e.g. role slug or user ID)
     * 
     * @return AAM_Core_Contract_Subject
     * 
     * @access public
     * @throws Exception If subject type is not valid
     */
    public function getSubject($type, $id = null) {
        switch($type) {
            case self::SUBJECT_USER:
                $subject = $this->getUserSubject($id);
                break;
            
            case self::SUBJECT_ROLE:
                $subject = $this->getRoleSubject($id);
                break;
            
            case self::SUBJECT_VISITOR:
                $subject = $this->getVisitorSubject();
                break;
            
            case self::SUBJECT_DEFAULT:
                $subject = $this->getDefaultSubject();
                break;
            
            default:
                throw new Exception('Invalid subject type');
        }
        
        return $subject;
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