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
 * Introduction/welcome service
 *
 * @package AAM
 * @version 6.7.0
 */
class AAM_Service_Network
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * DB cache option
     *
     * @version 6.7.0
     */
    const CACHE_DB_OPTION = 'aam_network_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.7.0
     */
    const FEATURE_FLAG = 'core.service.network.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.7.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Network::register();
                }, 1);
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Network', AAM_KEY),
                    'description' => __('WP Network Sites Management', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 1);
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Network::bootstrap();
}