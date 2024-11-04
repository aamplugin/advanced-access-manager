<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * API Route service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_ApiRoute
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.api_route.enabled';

    /**
     * Default configurations
     *
     * @version 6.9.34
     */
    const DEFAULT_CONFIG = [
        self::FEATURE_FLAG      => true,
        'core.settings.xmlrpc'  => true,
        'core.settings.restful' => true
    ];

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
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_ApiRoute::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('API Routes', AAM_KEY),
                    'description' => __('Manage access to any individual RESTful endpoint for any role, user or unauthenticated application request. The service works great with JWT service that authenticate requests with JWT Bearer token.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 45);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize API Route hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            add_filter('aam_settings_list_filter', function ($settings, $type) {
                if ($type === 'core') {
                    $service  = AAM::api()->configs();
                    $settings = array_merge($settings, array(
                        'core.settings.xmlrpc' => array(
                            'title'       => __('XML-RPC WordPress API', AAM_KEY),
                            'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Remote procedure call (RPC) interface is used to manage WordPress website content and features. For more information check %sXML-RPC Support%s article.', 'b'), '<a href="https://codex.wordpress.org/XML-RPC_Support">', '</a>'),
                            'value'       => $service->get_config('core.settings.xmlrpc')
                        ),
                        'core.settings.restful' => array(
                            'title'       => __('RESTful WordPress API', AAM_KEY),
                            'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('[Note!] If disabled, the AAM UI may not function as expected. The RESTful interface is used to manage WordPress website content and features. For detail, refer to %sREST API handbook%s.', 'b'), '<a href="https://developer.wordpress.org/rest-api/">', '</a>'),
                            'value'       => $service->get_config('core.settings.restful')
                        )
                    ));
                }

                return $settings;
            }, 10, 2);
        }

        // Register RESTful API endpoints
        AAM_Restful_ApiRouteService::bootstrap();

        // Disable XML-RPC if needed
        add_filter('xmlrpc_enabled', function($enabled) {
            if (AAM::api()->configs()->get_config(
                'core.settings.xmlrpc') === false
            ) {
                $enabled = false;
            }

            return $enabled;
        }, PHP_INT_MAX);

        // Disable RESTful API if needed
        add_filter(
            'rest_authentication_errors',
            function ($response) {
                if (!current_user_can('aam_manager')
                    && !is_wp_error($response)
                    && !AAM::api()->configs()->get_config(
                            'core.settings.restful'
                )) {
                    $response = new WP_Error(
                        'rest_access_disabled',
                        __('RESTful API is disabled', AAM_KEY),
                        array('status' => 403)
                    );
                }

                return $response;
            },
            PHP_INT_MAX
        );

        // Register API manager is applicable
        add_filter('rest_pre_dispatch', function($response, $_, $request) {
            return $this->_rest_pre_dispatch($response, $request);
        }, PHP_INT_MAX, 3);

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::API_ROUTE
                ) {
                    $resource = new AAM_Framework_Resource_ApiRoute(
                        $access_level, $resource_id
                    );
                }

                return $resource;
            }, 10, 4
        );
    }

    /**
     * Authorize REST request
     *
     * Based on the matched route, check if it is disabled for current user
     *
     * @param WP_Error|null   $response
     * @param WP_REST_Request $request
     *
     * @return WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    private function _rest_pre_dispatch($response, $request)
    {
        if (!is_wp_error($response)) {
            if (AAM::api()->api_routes()->is_restricted($request)) {
                $response = new WP_Error(
                    'rest_access_denied',
                    __('Access Denied', AAM_KEY),
                    array('status' => 401)
                );
            }
        }

        return $response;
    }

}