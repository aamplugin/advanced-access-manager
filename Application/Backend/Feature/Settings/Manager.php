<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Utility manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_Manager  extends AAM_Backend_Feature_Abstract {
    
    /**
     * Save AAM option
     * 
     * @return string
     *
     * @access public
     */
    public function save() {
        $param = filter_input(INPUT_POST, 'param');
        $value = filter_input(INPUT_POST, 'value');
        
        AAM_Core_Config::set($param, $value);
        
        return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * Clear all AAM settings
     * 
     * @return string
     * 
     * @access public
     */
    public function clearSettings() {
        AAM_Core_API::clearSettings();

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Clear AAM cache manually
     * 
     * @return string
     * 
     * @access public
     */
    public function clearCache() {
        AAM_Core_API::clearCache();

        return wp_json_encode(array('status' => 'success'));
    }
    
}