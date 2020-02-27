<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Capability service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Capability
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.capability.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Capability::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function($services) {
                $services[] = array(
                    'title'       => __('Capabilities', AAM_KEY),
                    'description' => __('Manage list of all the registered with WordPress core capabilities for any role or individual user. The service allows to create new or update and delete existing capabilities. Very powerful set of tools for more advanced user/role access management.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 15);
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Capability::bootstrap();
}