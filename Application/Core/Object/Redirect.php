<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access denied redirect object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Redirect extends AAM_Core_Object {
    
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
        parent::__construct($subject);

        $this->initialize();
    }
    
    /**
     * 
     */
    public function initialize() {
        $this->read();
    }
    
    /**
     *
     * @return void
     *
     * @access public
     */
    public function read() {
        $option = $this->getSubject()->readOption('redirect');
       
        //inherit from default Administrator role
        if (empty($option)) {
             //inherit from parent subject
            $option = $this->getSubject()->inheritFromParent('redirect');
            
            if (empty($option)) {
                $option = array();
                $this->readByArea('frontend', $option);
                $this->readByArea('backend', $option);
            }
        } else {
            $this->setOverwritten(true);
        }
        
        $this->setOption($option);
    }
    
    /**
     * 
     * @param type $area
     * @param type $option
     */
    protected function readByArea($area, &$option) {
        $type = AAM_Core_Config::get("{$area}.redirect.type");
        if ($type) {
            $option["{$area}.redirect.type"] = $type;
            $option["{$area}.redirect.{$type}"] = AAM_Core_Config::get(
                    "{$area}.redirect.{$type}"
            );
        }
    }

    /**
     * Save options
     * 
     * @param string  $property
     * @param boolean $value
     * 
     * @return boolean
     * 
     * @access public
     */
    public function save($property, $value) {
        $option            = $this->getOption();
        $option[$property] = $value;
        
        return $this->getSubject()->updateOption($option, 'redirect');
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return $this->getSubject()->deleteOption('redirect');
    }

    /**
     * 
     * @param string $param
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($param) {
        $option = $this->getOption();
        
        return !empty($option[$param]);
    }
    
    /**
     * 
     * @param string $param
     * 
     * @return boolean
     * 
     * @access public
     */
    public function get($param) {
        $option = $this->getOption();
        
        return !empty($option[$param]) ? $option[$param] : null;
    }
    
}