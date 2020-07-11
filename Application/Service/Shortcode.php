<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM custom shortcodes service
 *
 * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/90
 * @since 6.0.0 Initial implementation of the method
 *
 * @package AAM
 * @version 6.6.0
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
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/90
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.6.0
     */
    protected function initializeHooks()
    {
        if (!is_admin()) {
            add_shortcode('aam', function ($args, $content) {
                $shortcode = new AAM_Shortcode_Factory($args, $content);

                return $shortcode->process();
            });
            add_shortcode('aam-login', function($args, $content) {
                $shortcode = new AAM_Shortcode_Handler_LoginForm($args, $content);

                return $shortcode->run();
            });
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Shortcode::bootstrap();
}