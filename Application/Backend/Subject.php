<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend subject
 * 
 * Currently managed subject. Based on the HTTP request critiria, define what subject
 * is currently managed with AAM UI.
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Subject {
    
    /**
     * Single instance of itself
     * 
     * @var AAM_Backend_Subject
     * 
     * @access protected
     * @static 
     */
    protected static $instance = null;
    
    /**
     * Subject information
     * 
     * @var AAM_Core_Subject
     * 
     * @access protected
     */
    protected $subject = null;
    
    /**
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        $subject = AAM_Core_Request::request('subject');
        
        if ($subject) {
            $this->initRequestedSubject(
                    $subject, AAM_Core_Request::request('subjectId')
            );
        } else {
            $this->initDefaultSubject();
        }
    }
    
    /**
     * Initialize requested subject
     * 
     * @param string $type
     * @param string $id
     * 
     * @return void
     * 
     * @access protected
     */
    protected function initRequestedSubject($type, $id) {
        $classname = 'AAM_Core_Subject_' . ucfirst($type);
        
        if (class_exists($classname)) {
            $subject = new $classname(stripslashes($id));
            
            // Load access policy
            $subject->getObject('policy');
            
            $this->setSubject($subject);
        }
    }
    
    /**
     * Initialize default subject
     * 
     * Based on user permissions, pick the first available subject that current user
     * can manage with AAM UI
     * 
     * @return void
     * 
     * @access protected
     */
    protected function initDefaultSubject() {
        // This cover the scenario when we directly go to user e.g. ?page=aam&user=38
        $forceUser = AAM_Core_Request::get('user');
        
        // TODO: The aam_list_roles is legacy and can be removed in Oct 2021
        if (!$forceUser && (current_user_can('aam_manage_roles') || current_user_can('aam_list_roles'))) {
            $roles = array_keys(get_editable_roles());
            $this->initRequestedSubject(AAM_Core_Subject_Role::UID, array_shift($roles));
        // TODO: The list_users is legacy and can be removed in Oct 2021
        } elseif (current_user_can('aam_manage_users') || current_user_can('list_users')) {
            $this->initRequestedSubject(
                AAM_Core_Subject_User::UID,
                ($forceUser ? intval($forceUser) : get_current_user_id())
            );
        // TODO: The aam_list_roles is legacy and can be removed in Oct 2021
        } elseif (current_user_can('aam_manage_visitors')) {
            $this->initRequestedSubject(AAM_Core_Subject_Visitor::UID, null);
        } elseif (current_user_can('aam_manage_default')) {
            $this->initRequestedSubject(AAM_Core_Subject_Default::UID, null);
        }
    }
    
    /**
     * Set subject
     * 
     * @param AAM_Core_Subject $subject
     * 
     * @access protected
     */
    protected function setSubject(AAM_Core_Subject $subject) {
        $this->subject = $subject;
    }
    
    /**
     * Get subject property
     * 
     * @return mixed
     * 
     * @access public
     */
    public function __get($name) {
        return (!empty($this->subject->$name) ? $this->subject->$name : null);
    }
    
    /**
     * Call subject's method
     * 
     * @param string $name
     * @param array  $args
     * 
     * @return mized
     * 
     * @access public
     */
    public function __call($name, $args) {
        //make sure that method is callable
        if (method_exists($this->subject, $name)) {
            $response = call_user_func_array(array($this->subject, $name), $args);
        } else {
            $response = null;
        }

        return $response;
    }
    
    /**
     * Get AAM subject
     * 
     * @return AAM_Core_Subject
     * 
     * @access public
     */
    public function get() {
        return $this->subject;
    }
    
    /**
     * Get single instance of the subject
     * 
     * @return AAM_Backend_Subject
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
}