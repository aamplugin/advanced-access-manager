<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin toolbar object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Toolbar extends AAM_Core_Object {

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
        
        $option = $this->getSubject()->readOption('toolbar');
        
        if (!empty($option)) {
            $this->setOverwritten(true);
        }
        
        // Load settings from Access & Security Policy
        if (empty($option)) {
            $stms = AAM_Core_Policy_Factory::get($subject)->find("/^Toolbar:/i");
            
            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);
                $option[$chunks[1]] = ($stm['Effect'] === 'deny' ? 1 : 0);
            }
        }
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('toolbar');
        }
        
        $this->setOption($option);
    }

    /**
     * Check is item defined
     * 
     * Check if toolbar item defined in options based on the id
     * 
     * @param string $item
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($item, $both = false) {
        $options = $this->getOption();
        
        // Step #1. Check if toolbar item is directly restricted
        $direct = !empty($options[$item]);
        
        // Step #2. Check if whole branch is restricted
        $branch = ($both && !empty($options['toolbar-' . $item]));
        
        return $direct || $branch;
    }
    
    /**
     * Allow access to a specific menu
     * 
     * @param string $menu
     * 
     * @return boolean
     * 
     * @access public
     */
    public function allow($menu) {
        return $this->save($menu, 0);
    }
    
    /**
     * Deny access to a specific menu
     * 
     * @param string $menu
     * 
     * @return boolean
     * 
     * @access public
     */
    public function deny($menu) {
        return $this->save($menu, 1);
    }

    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($item = null, $value = null) {
        if (!is_null($item)) { // keep it compatible with main Manager.save
            $this->updateOptionItem($item, $value);
        }
        
        return $this->getSubject()->updateOption($this->getOption(), 'toolbar');
    }
    
    /**
     * Reset default settings
     * 
     * @return bool
     * 
     * @access public
     */
    public function reset() {
        return $this->getSubject()->deleteOption('toolbar');
    }
    
    /**
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return AAM::api()->mergeSettings($external, $this->getOption(), 'toolbar');
    }

}