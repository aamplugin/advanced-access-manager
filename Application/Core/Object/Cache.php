<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM cache object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Cache extends AAM_Core_Object {
    
    /**
     * Cache updated flag
     * 
     * @var boolean
     * 
     * @access protected 
     */
    protected $updated = false;
    
    /**
     * Is cache enabled?
     * 
     * @var boolean
     * 
     * @access protected 
     */
    protected $enabled = true;

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
        
        // Determine if cache is enabled
        $action   = AAM_Core_Request::request('action');
        $triggers = array('edit', 'editpost');
        $status   = AAM_Core_Config::get('core.cache.status', 'enabled');
        
        if (AAM::isAAM() || ($status !== 'enabled') || in_array($action, $triggers, true)) {
            $this->enabled = false;
        }
        
        if ($this->enabled) {
            // Register shutdown hook
            register_shutdown_function(array($this, 'save'));
            
            $this->reload();
        }
    }
    
    /**
     * 
     */
    public function reload() {
        // Just get the cache from current subject level. Do not trigger
        // inheritance chain!
        $this->setOption($this->getSubject()->readOption('cache'));
    }
    
    /**
     * 
     * @param type $type
     * @param type $id
     * @param type $value
     */
    public function add($type, $id, $value) {
        $option = $this->getOption();
        
        $limit  = AAM_Core_Config::get('core.cache.limit', 1000);
        if (isset($option[$type][$id]) && (count($option[$type][$id]) >= $limit)) {
            array_shift($option[$type][$id]);
        }

        $option[$type][$id] = $value;
        $this->setOption($option);
        
        $this->updated = true;
    }
    
    /**
     * Get cache
     * 
     * @param string     $type
     * @param string|int $id
     * @param mixed      $default
     * 
     * @return mixed
     * 
     * @access public
     */
    public function get($type, $id = 0, $default = array()) {
        $option = $this->getOption();
        
        return (isset($option[$type][$id]) ? $option[$type][$id] : $default);
    }
    
    /**
     * Save cache
     * 
     * @return bool
     * 
     * @access public
     */
    public function save() {
        if ($this->enabled && $this->updated) {
            $this->getSubject()->updateOption($this->getOption(), 'cache');
        }
        
        return true;
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return $this->getSubject()->deleteOption('cache');
    }

}