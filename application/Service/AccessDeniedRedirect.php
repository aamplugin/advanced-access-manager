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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/309
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
 *               https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Service_AccessDeniedRedirect
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'access-denied-redirect';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.access_denied_redirect.enabled';

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

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Redirect::register();
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
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/309
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
     *               https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function initialize_hooks()
    {
        add_action('aam_access_denied_redirect_handler_filter', function($handler) {
            if (is_null($handler)) {
                $handler = function() {
                    $is_post  = $_SERVER['REQUEST_METHOD'] === 'POST';
                    $is_rest = (defined('REST_REQUEST') && REST_REQUEST);

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
                            AAM_Framework_Utility::do_redirect($redirect);
                        }
                    }
                };
            }

            return $handler;
        });

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter',
            [ $this, 'generate_policy' ],
            10,
            3
        );

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

        // Service fetch
        $this->registerService();
    }

     /**
     * Generate Access Denied Redirect policy params
     *
     * @param array   $policy
     * @param string  $resource_type
     * @param array   $options
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    public function generate_policy($policy, $resource_type, $options)
    {
        if ($resource_type === AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT) {
            if (!empty($options)) {
                $params = array();

                foreach($options as $key => $val) {
                    $parts = explode('.', $key);

                    if ($parts[2] === 'type') {
                        $destination = $options["{$parts[0]}.redirect.{$val}"];

                        $value = array(
                            'Type' => $val
                        );

                        if ($val === 'page') {
                            $page = get_post($destination);

                            if (is_a($page, 'WP_Post')) {
                                $value['PageSlug'] = $page->post_name;
                            } else{
                                $value['PageId'] = intval($destination);
                            }
                        } elseif ($val  === 'url') {
                            $value['Url'] = trim($destination);
                        } elseif ($val === 'callback') {
                            $value['Callback'] = trim($destination);
                        } elseif ($val === 'message') {
                            $value['Message'] = esc_js($destination);
                        }

                        $params[] = array(
                            'Key'   => 'redirect:on:access-denied:' . $parts[0],
                            'Value' => $value
                        );
                    }
                }

                $policy["Param"] = array_merge($policy["Param"], $params);
            }
        }

        return $policy;
    }

}