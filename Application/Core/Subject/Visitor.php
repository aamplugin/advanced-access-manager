<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Visitor subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Subject_Visitor extends AAM_Core_Subject {

    /**
     * Subject UID: VISITOR
     */
    const UID = 'visitor';

    /**
     *
     * @param type $value
     * @param type $object
     * @param type $id
     * @return type
     */
    public function updateOption($value, $object, $id = 0) {
        return AAM_Core_API::updateOption(
                        $this->getOptionName($object, $id), $value
        );
    }

    /**
     *
     * @param type $object
     * @param type $id
     * @return type
     */
    public function readOption($object, $id = 0) {
        return AAM_Core_API::getOption(
                        $this->getOptionName($object, $id)
        );
    }

    /**
     * 
     * @param type $object
     * @param type $id
     * @return type
     */
    public function getOptionName($object, $id) {
        return 'aam_' . self::UID . "_{$object}" . ($id ? "_{$id}" : '');
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
    public function getParent() {
        return AAM_Core_Subject_Default::getInstance();
    }

    /**
     * 
     * @return type
     */
    public function getName() {
        return __('Anonymous', AAM_KEY);
    }
    
    /**
     * 
     * @return boolean
     */
    public function isVisitor() {
        return true;
    }
    
}