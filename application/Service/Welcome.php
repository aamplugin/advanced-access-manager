<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Introduction/welcome service
 *
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/327
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.18
 */
class AAM_Service_Welcome
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.welcome.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/327
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.18
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Welcome::register();
                }, 1);
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Welcome', AAM_KEY),
                    'description' => __('Introduction panel to the AAM functionality. This is just a simple tab that contains some introductory material to the AAM plugin and its capabilities.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 1);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            // Register RESTful API endpoints
            AAM_Core_Restful_SupportService::bootstrap();
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Welcome::bootstrap();
}