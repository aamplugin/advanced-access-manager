<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Capability object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Capability extends AAM_Core_Object {

    /**
     * Update subject's capability
     * 
     * @param string $capability
     * @param bool   $granted
     * 
     * @return bool
     * 
     * @access public
     */
    public function save($capability, $granted) {
        return $this->getSubject()->addCapability(
            $capability,
            intval($granted) ? true : false
        );
    }
    
    /**
     * Check if subject has specified capability
     * 
     * @param string $capability
     * 
     * @return bool
     * 
     * @access public
     */
    public function has($capability) {
        return $this->getSubject()->hasCapability($capability);
    }
    
    /**
     * Assign capability to user
     * 
     * @param string $capability
     * 
     * @return boolean
     * 
     * @access public
     */
    public function add($capability) {
        return $this->save($capability, 1);
    }
    
    /**
     * Remove capability from user
     * 
     * @param string $capability
     * 
     * @return boolean
     * 
     * @access public
     */
    public function remove($capability) {
        return $this->save($capability, 0);
    }

}