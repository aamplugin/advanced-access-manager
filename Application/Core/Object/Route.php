<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * API route object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Route extends AAM_Core_Object {

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
        
        $option = $this->getSubject()->readOption('route');
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('route');
        } else {
            $this->setOverwritten(true);
        }
        
        $this->setOption($option);
    }
    
    /**
     * Check if route is denied
     * 
     * @param string $type REST or XMLRPC
     * @param string $route
     * @param string $method
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($type, $route, $method = 'POST') {
        $options = $this->getOption();

        return !empty($options[$type][$route][$method]);
    }

    /**
     * Save menu option
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($type, $route, $method, $value) {
        $option = $this->getOption();
        $option[$type][$route][$method] = $value;
        $this->setOption($option);
        
        return $this->getSubject()->updateOption($this->getOption(), 'route');
    }
    
    /**
     * Reset default settings
     * 
     * @return bool
     * 
     * @access public
     */
    public function reset() {
        return $this->getSubject()->deleteOption('route');
    }

}