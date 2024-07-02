<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM services
 *
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/193
 * @since 6.0.0  Initial implementation of the method
 *
 * @package AAM
 * @version 6.9.34
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
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/193
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public static function getList()
    {
        $response = apply_filters('aam_service_list_filter', array());
        $service  = AAM_Framework_Manager::configs();

        // Get each service status
        foreach ($response as &$item) {
            $item['status'] = $service->get_config($item['setting']);
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