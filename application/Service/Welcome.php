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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
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
                    'description' => __('This service provides a simple overview of the plugin and its capabilities. It presents essential information about how AAM can enhance your experience and streamline your tasks. Explore the features and benefits of AAM and discover how it can help you achieve your goals efficiently.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 1);
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Welcome::bootstrap();
}