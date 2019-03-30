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
        
        $option = AAM_Core_Compatibility::convertRoute(
                $this->getSubject()->readOption('route')
        );
        
        if (!empty($option)) {
            $this->setOverwritten(true);
        }
       
        // Load settings from Access & Security Policy
        if (empty($option)) {
            $stms = AAM_Core_Policy_Factory::get($subject)->find("/^Route:/i");
           
            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);
                $method = (isset($chunks[3]) ? $chunks[3] : 'post');
                $id     = "{$chunks[1]}|{$chunks[2]}|{$method}";
                
                $option[$id] = ($stm['Effect'] === 'deny' ? 1 : 0);
            }
        }
        
        if (empty($option)) {
            $option = $this->getSubject()->inheritFromParent('route');
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
        $id      = strtolower("{$type}|{$route}|{$method}");
        
        return !empty($options[$id]);
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
        
        $id     = strtolower("{$type}|{$route}|{$method}");
        $option[$id] = $value;
        
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