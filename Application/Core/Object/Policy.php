<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Policy object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Policy extends AAM_Core_Object {

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
     * Initialize the policy rules for current subject
     * 
     * @return void
     * 
     * @access public
     */
    public function initialize() {
        $subject = $this->getSubject();
        $parent  = $subject->inheritFromParent('policy');
        
        // Prevent from any kind of surprises
        if(empty($parent) || !is_array($parent)) {
            $parent = array();
        }
        
        $option = $subject->readOption('policy');
        if (empty($option)) {
            $option = array();
        } else {
            $this->setOverwritten(true);
        }
        
        foreach($option as $key => $value) {
            $parent[$key] = $value; //override
        }
        
        $this->setOption($parent);
    }
    
    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($id, $effect) {
        $option      = $this->getOption();
        $option[$id] = intval($effect);

        $this->setOption($option);

        return $this->getSubject()->updateOption($this->getOption(), 'policy');
    }
    
    /**
     * Check if policy attached
     * 
     * @param int $id
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($id) {
        $option = $this->getOption();
        
        return !empty($option[$id]);
    }
    
    /**
     * 
     * @param type $id
     * 
     * @return type
     */
    public function delete($id) {
        $option = $this->getOption();
        if (isset($option[$id])) {
            unset($option[$id]);
        }
        $this->setOption($option);
        
        return $this->getSubject()->updateOption($this->getOption(), 'policy');
    }
    
    /**
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return AAM::api()->mergeSettings($external, $this->getOption(), 'policy');
    }
    
}