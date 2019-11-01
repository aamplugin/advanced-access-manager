<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM services
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Settings_Service extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_services';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'settings/service.php';

    /**
     * Get list of services
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public static function getList()
    {
        $response = apply_filters('aam_service_list_filter', array());

        // Get each service status
        foreach ($response as &$service) {
            $service['status'] = AAM_Core_Config::get($service['setting'], 1);
        }

        return $response;
    }

    /**
     * Register services settings tab
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'settings-services',
            'position'   => 1,
            'title'      => __('Services', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}