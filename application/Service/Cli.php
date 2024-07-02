<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WP CLI service
 *
 * @package AAM
 *
 * @version 6.7.0
 */
class AAM_Service_Cli
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.7.0
     */
    const FEATURE_FLAG = 'core.service.cli.enabled';

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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('WP CLI', AAM_KEY),
                    'description' => __('Collection of WP CLI command that facilitate various AAM features.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 30);
        }

        if (AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.7.0
     */
    protected function initializeHooks()
    {
        // Register WP-CLI commands
        if (class_exists('WP_CLI')) {
            WP_CLI::add_command('aam', 'AAM_Core_Cli');
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Cli::bootstrap();
}