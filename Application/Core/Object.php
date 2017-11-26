<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract object class
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
abstract class AAM_Core_Object {

    /**
     * Subject
     *
     * @var AAM_Core_Subject
     *
     * @access private
     */
    private $_subject = null;

    /**
     * Object options
     *
     * @var array
     *
     * @access private
     */
    private $_option = array();

    /**
     * Inheritance indicator
     * 
     * @var null|string
     * 
     * @access private 
     */
    private $_inherited = null;
    
    /**
     * Overwritten indicator
     * 
     * If settings for specific object were detected befor inheritance mechanism
     * kicked off, then it it considered overwritten
     * 
     * @var boolean
     * 
     * @access private 
     */
    private $_overwritten = false;

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     */
    public function __construct(AAM_Core_Subject $subject) {
        $this->setSubject($subject);
    }

    /**
     * Set current subject
     *
     * Either it is User or Role
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     */
    public function setSubject(AAM_Core_Subject $subject) {
        $this->_subject = $subject;
    }

    /**
     * Get Subject
     *
     * @return AAM_Core_Subject
     *
     * @access public
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * Set Object options
     * 
     * @param mixed $option
     * 
     * @return void
     * 
     * @access public
     */
    public function setOption($option) {
        $this->_option = (is_array($option) ? $option : array());
    }

    /**
     * Get Object options
     * 
     * @return mixed
     * 
     * @access public
     */
    public function getOption() {
        return $this->_option;
    }

    /**
     * Set Inherited flag
     * 
     * @param string $inherited
     * 
     * @return void
     */
    public function setInherited($inherited) {
        $this->_inherited = $inherited;
    }

    /**
     * Get Inherited flag
     * 
     * @return null|string
     * 
     * @access public
     */
    public function getInherited() {
        return $this->_inherited;
    }
    
    /**
     * Set overwritten flat
     * 
     * @param boolean $overwritten
     * 
     * @return void
     * 
     * @access public
     */
    public function setOverwritten($overwritten = true) {
        $this->_overwritten = $overwritten;
    }
    
    /**
     * Check if options are overwritten
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isOverwritten () {
        return $this->_overwritten;
    }

}