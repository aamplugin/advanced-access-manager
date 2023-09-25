<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Support service
 *
 * @package AAM
 * @version 6.9.15
 */
class AAM_Service_Support
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.9.15
     */
    const FEATURE_FLAG = 'core.service.support.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.15
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Support::register();
                }, 1);
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Support', AAM_KEY),
                    'description' => __('Submit your support request from AAM page. AAM team will follow-up with the answer via email.', AAM_KEY),
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
    AAM_Service_Support::bootstrap();
}