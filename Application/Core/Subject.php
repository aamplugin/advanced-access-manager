<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
abstract class AAM_Core_Subject {

    /**
     * Subject ID
     *
     * Whether it is User ID or Role ID
     *
     * @var string|int
     *
     * @access private
     */
    private $_id;

    /**
     * WordPres Subject
     *
     * It can be WP_User or WP_Role, based on what class has been used
     *
     * @var WP_Role|WP_User
     *
     * @access private
     */
    private $_subject;

    /**
     * List of Objects to be access controlled for current subject
     *
     * All access control objects like Admin Menu, Metaboxes, Posts etc
     *
     * @var array
     *
     * @access private
     */
    private $_objects = array();

    /**
     * Constructor
     *
     * @param string|int $id
     *
     * @return void
     *
     * @access public
     */
    public function __construct($id = '') {
        //set subject
        $this->setId($id);
        //retrieve and set subject itself
        $this->setSubject($this->retrieveSubject());
    }

    /**
     * Trigger Subject native methods
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     */
    public function __call($name, $args) {
        $subject = $this->getSubject();
        
        //make sure that method is callable
        if (method_exists($subject, $name)) {
            $response = call_user_func_array(array($subject, $name), $args);
        } else {
            $response = null;
        }

        return $response;
    }

    /**
     * Get Subject's native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function __get($name) {
        $subject = $this->getSubject();
        
        return (!empty($subject->$name) ? $subject->$name : null);
    }

    /**
     * Set Subject's native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function __set($name, $value) {
        $subject = $this->getSubject();
        
        if ($subject) {
            $subject->$name = $value;
        }
    }

    /**
     * Set Subject ID
     *
     * @param string|int
     *
     * @return void
     *
     * @access public
     */
    public function setId($id) {
        $this->_id = $id;
    }

    /**
     * Get Subject ID
     *
     * @return string|int
     *
     * @access public
     */
    public function getId() {
        return $this->_id;
    }
    
    /**
     * Get subject name
     * 
     * @return string
     * 
     * @access public
     */
    public function getName() {
        return '';
    }
    
    /**
     * 
     * @return int
     */
    public function getMaxLevel() {
        return 0;
    }

    /**
     * Get Subject
     *
     * @return WP_Role|WP_User
     *
     * @access public
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * Set Subject
     *
     * @param WP_Role|WP_User $subject
     *
     * @return void
     *
     * @access public
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    /**
     * Get Individual Object
     *
     * @param string $type
     * @param mixed  $id
     *
     * @return AAM_Core_Object
     *
     * @access public
     */
    public function getObject($type, $id = 'none', $param = null) {
        $object = null;
        
        //performance optimization
        $id = (is_scalar($id) ? $id : 'none'); //prevent from any surprises
        
        //check if there is an object with specified ID
        if (!isset($this->_objects[$type][$id])) {
            $classname = 'AAM_Core_Object_' . ucfirst($type);
            
            if (class_exists($classname)) {
                $object = new $classname($this, (is_null($param) ? $id : $param));
            }
            
            $object = apply_filters('aam-object-filter', $object, $type, $id, $this);
            
            if (is_a($object, 'AAM_Core_Object')) {
                $this->_objects[$type][$id] = $object;
            }
        } else {
            $object = $this->_objects[$type][$id];
        }

        return $object;
    }

    /**
     * Check if subject has capability
     * 
     * @param string $capability
     * 
     * @return boolean
     * 
     * @access public
     */
    public function hasCapability($capability) {
        $subject = $this->getSubject();
        
        return ($subject ? $subject->has_cap($capability) : false);
    }
    
    /**
     * Save option
     * 
     * @param string $param
     * @param mixed  $value
     * @param string $object
     * @param mixed  $objectId
     * 
     * @return boolean
     * 
     * @access public
     */
    public function save($param, $value, $object, $objectId = 0) {
        return $this->getObject($object, $objectId)->save($param, $value);
    }

    /**
     * Reset object
     *
     * @param string $object
     * 
     * @return boolean
     * 
     * @access public
     */
    public function resetObject($object) {
        return $this->deleteOption($object);
    }
    
    /**
     * Delete option
     * 
     * @param string $object
     * @param mixed  $id
     * 
     * @return boolean
     * 
     * @access public
     */
    public function deleteOption($object, $id = 0) {
        return AAM_Core_API::deleteOption($this->getOptionName($object, $id));
    }

    /**
     * Retrieve list of subject's capabilities
     *
     * @return array
     *
     * @access public
     */
    public function getCapabilities() {
        return array();
    }

    /**
     * Retrieve subject based on used class
     *
     * @return void
     *
     * @access protected
     */
    protected function retrieveSubject() {
        return null;
    }
    
    /**
     * 
     */
    abstract public function getOptionName($object, $id);
    
    /**
     * Read object from parent subject
     * 
     * @param string $object
     * @param mixed  $id
     * 
     * @return mixed
     * 
     * @access public
     */
    public function inheritFromParent($object, $id = '', $param = null){
        if ($subject = $this->getParent()){
            $option = $subject->getObject($object, $id, $param)->getOption();
        } else {
            $option = null;
        }
        
        return $option;
    }
    
    /**
     * Retrieve parent subject
     * 
     * If there is no parent subject, return null
     * 
     * @return AAM_Core_Subject|null
     * 
     * @access public
     */
    public function getParent() {
        return null;
    }
    
}