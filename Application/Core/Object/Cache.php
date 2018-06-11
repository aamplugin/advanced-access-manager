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
        
        if (!AAM::isAAM() 
                && (AAM_Core_Config::get('core.cache.status', 'enabled') == 'enabled')) {
            // Register shutdown hook
            add_action('shutdown', array($this, 'save'));

            // Just get the cache from current subject level. Do not trigger
            // inheritance chain!
            $this->setOption($this->getSubject()->readOption('cache'));
        }
    }
    
    /**
     * 
     * @param type $type
     * @param type $id
     * @param type $value
     */
    public function add($type, $id, $value) {
        $option = $this->getOption();
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
        if ($this->updated) {
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
    
    /**
     * Read object from parent subject
     * 
     * @return mixed
     * 
     * @access public
     */
    public function inheritFromParent(){
        if ($subject = $this->getParent()){
            $option = $subject->getObject('cache')->getOption();
        } else {
            $option = array();
        }
        
        return $option;
    }
    
}