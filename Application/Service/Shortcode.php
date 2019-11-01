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
 * AAM custom shortcodes service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Shortcode
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.shortcode.enabled';

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
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Shortcodes', AAM_KEY),
                    'description' => __('Classic WordPress shortcodes that allow to manage access to parts of a frontent content as well as some UI helpers.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 25);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize Shortcode service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function initializeHooks()
    {
        if (!is_admin()) {
            add_shortcode('aam', function ($args, $content) {
                $shortcode = new AAM_Shortcode_Factory($args, $content);

                return $shortcode->process();
            });
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Shortcode::bootstrap();
}