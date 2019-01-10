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
        
        $caps = $this->getSubject()->getCapabilities();
        
        // Load Capabilities from the policy
        $stms = AAM_Core_Policy_Manager::getInstance()->find(
            "/^Capability:/i", $subject
        );
        
        foreach($stms as $key => $stm) {
            $chunks = explode(':', $key);
            if (count($chunks) === 2) {
                $caps[$chunks[1]] = ($stm['Effect'] === 'allow' ? 1 : 0);
            }
        }
        
        //check if capabilities are overwritten but only for user subject
        if (is_a($this->getSubject(), 'AAM_Core_Subject_User')) {
            $userCaps = get_user_option(
                    AAM_Core_Subject_User::AAM_CAPKEY, $this->getSubject()->getId()
            );
            if (!empty($userCaps)) {
                $caps = array_merge($caps, $userCaps);
                $this->setOverwritten(true);
            }
        }
        
        $this->setOption($caps);
    }

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
        if (intval($granted)) {
            $result = $this->getSubject()->addCapability($capability);
        } else {
            $result = $this->getSubject()->removeCapability($capability);
        }
        
        return $result;
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