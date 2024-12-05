<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * 404 redirect service
 *
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/292
 * @since 6.8.5  https://github.com/aamplugin/advanced-access-manager/issues/215
 * @since 6.4.0  Refactored to use 404 object instead of AAM config
 *               https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0  Initial implementation of the service
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Service_NotFoundRedirect
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.not_found_redirect.enabled';

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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->config->get(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_NotFoundRedirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('404 Redirect', AAM_KEY),
                    'description' => __('Handle frontend 404 (Not Found) redirects for any group of users or individual user.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 40);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize the service hooks
     *
     * @return void
     *
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/292
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.12
     */
    protected function initialize_hooks()
    {
        add_action('wp', function() {
            global $wp_query;

            if ($wp_query->is_404) { // Handle 404 redirect
                $redirect = AAM::api()->not_found_redirect()->get_redirect();

                if ($redirect['type'] !== 'default') {
                    AAM::api()->redirect->do_redirect($redirect);
                }
            }
        });

        // Register the RESTful API
        AAM_Restful_NotFoundRedirectService::bootstrap();
    }

}