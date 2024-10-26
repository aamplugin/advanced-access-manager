<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_AccessDeniedRedirect
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.access_denied_redirect.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_AccessDeniedRedirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Access Denied Redirect', AAM_KEY),
                    'description' => __('Manage the default access-denied redirect separately for the frontend and backend when access to any protected website resource is denied.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 25);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize Access Denied Redirect hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        add_action('aam_access_denied_redirect_handler_filter', function($handler) {
            if (is_null($handler)) {
                $handler = function() {
                    $is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
                    $is_rest = defined('REST_REQUEST') && REST_REQUEST;

                    if (!$is_post && !$is_rest) {
                        $service  = AAM::api()->user()->access_denied_redirect();
                        $redirect = $service->get_redirect(
                            (is_admin() ? 'backend' : 'frontend')
                        );

                        if ($redirect['type'] === 'default') {
                            if (isset($redirect['http_status_code'])) {
                                $status_code = $redirect['http_status_code'];
                            } else {
                                $status_code = 401;
                            }

                            wp_die(
                                __('The access is denied.', AAM_KEY),
                                __('Access Denied', AAM_KEY),
                                apply_filters('aam_wp_die_args_filter', [
                                    'exit'     => true,
                                    'response' => $status_code
                                ])
                            );
                        } else {
                            AAM_Framework_Utility_Redirect::do_redirect($redirect);
                        }
                    }
                };
            }

            return $handler;
        });

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT
                ) {
                    $resource = new AAM_Framework_Resource_AccessDeniedRedirect(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_AccessDeniedRedirectService::bootstrap();
    }

}