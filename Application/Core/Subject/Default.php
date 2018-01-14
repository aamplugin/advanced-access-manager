<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Default subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Subject_Default extends AAM_Core_Subject {

    /**
     * Subject UID: DEFAULT
     */
    const UID = 'default';
    
    /**
     *
     * @var type 
     */
    protected static $instance = null;
    
    /**
     *
     * @param type $value
     * @param type $object
     * @param type $object_id
     * @return type
     */
    public function updateOption($value, $object, $object_id = 0) {
        return AAM_Core_API::updateOption(
                        $this->getOptionName($object, $object_id), $value
        );
    }

    /**
     *
     * @param type $object
     * @param type $object_id
     * @param type $default
     * @return type
     */
    public function readOption($object, $object_id = 0, $default = null) {
        return AAM_Core_API::getOption(
                        $this->getOptionName($object, $object_id), $default
        );
    }

    /**
     *
     * @param type $object
     * @param type $id
     * @return string
     */
    public function getOptionName($object, $id) {
        return "aam_{$object}" . ($id ? "_{$id}_" : '_') . self::UID;
    }

    /**
     *
     * @return type
     */
    public function getUID() {
        return self::UID;
    }
    
    /**
     * 
     * @return type
     */
    public function getName() {
        return __('All Users, Roles and Visitor', AAM_KEY);
    }
    
    /**
     * 
     * @return boolean
     */
    public function isDefault() {
        return true;
    }
    
    /**
     * 
     * @return type
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }

}