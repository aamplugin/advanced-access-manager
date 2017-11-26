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
                        'export', array('system' => 'roles,utilities,configpress')
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
        global $wpdb;

        //clear wp_options
        $oquery = "DELETE FROM {$wpdb->options} WHERE (`option_name` LIKE %s) AND ";
        $oquery .= "(`option_name` NOT IN ('aam-extensions', 'aam-uid'))";
        $wpdb->query($wpdb->prepare($oquery, 'aam%'));

        //clear wp_postmeta
        $pquery = "DELETE FROM {$wpdb->postmeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($pquery, 'aam-post-access-%'));

        //clear wp_usermeta
        $uquery = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($uquery, 'aam%'));

        $mquery = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($mquery, $wpdb->prefix . 'aam%'));

        return json_encode(array('status' => 'success'));
    }

    /**
     * 
     * @return type
     */
    public function clearCache() {
        AAM_Core_Cache::clear();

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
