<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy manager factory
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since AAM v5.7.2
 */
final class AAM_Core_Policy_Factory {
    
    /**
     * Collection of instances
     * 
     * @var array 
     * 
     * @access private
     * @static
     */
    private static $_instances = array();
    
    /**
     * Get single instance of itself
     * 
     * @param AAM_Core_Subject $subject
     * 
     * @return AAM_Core_Policy_Manager
     * 
     * @access public
     * @static
     */
    public static function get(AAM_Core_Subject $subject = null) {
        if (is_null($subject)) {
            $subject = AAM::getUser();
        }
        
        $id  = $subject->getId(); 
        $sid = $subject->getUID() . (empty($id) ? '' : '_' . $id);
        
        if (!isset(self::$_instances[$sid])) {
            self::$_instances[$sid] = new AAM_Core_Policy_Manager($subject);
        }
        
        return self::$_instances[$sid];
    }
    
}