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
        $status = AAM_Core_Config::get('core.cache.status', 'enabled');
        
        if (AAM::isAAM() || ($status !== 'enabled')) {
            $this->enabled = false;
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
        
        $limit  = AAM_Core_Config::get('core.cache.limit', 1000);
        if (isset($option[$type][$id]) && (count($option[$type][$id]) >= $limit)) {
            array_shift($option[$type][$id]);
        }

        $option[$type][$id] = $value;
        $this->setOption($option);
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