<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend tools settings
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_Tools extends AAM_Backend_Feature_Abstract {

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'settings/tools.phtml';
    }

    /**
     * 
     * @return type
     */
    public function export() {
        $exporter = new AAM_Core_Exporter(AAM_Core_Config::get(
            'feature.export', array('system' => 'roles,utilities,configpress')
        ));

        return json_encode(array(
            'status' => 'success',
            'content' => base64_encode(json_encode($exporter->run()))
        ));
    }
    
    /**
     * 
     * @return type
     */
    public function import() {
        $importer = new AAM_Core_Importer(filter_input(INPUT_POST, 'json'));

        return json_encode(array('status' => $importer->run()));
    }

    /**
     * Clear all AAM settings
     * 
     * @global wpdb $wpdb
     * 
     * @return string
     * 
     * @access public
     */
    public function clear() {
        AAM_Core_API::clearSettings();

        return json_encode(array('status' => 'success'));
    }

    /**
     * 
     * @return type
     */
    public function clearCache() {
        AAM_Core_API::clearCache();

        return json_encode(array('status' => 'success'));
    }

    /**
     * Register Contact/Hire feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid' => 'settings-tools',
            'position' => 10,
            'title' => __('Tools', AAM_KEY),
            'capability' => 'aam_manage_settings',
            'type' => 'settings',
            'view' => __CLASS__
        ));
    }

}