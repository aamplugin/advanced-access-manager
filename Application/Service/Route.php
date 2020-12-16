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
 * @since 6.7.2 https://github.com/aamplugin/advanced-access-manager/issues/163
 * @since 6.7.0 https://github.com/aamplugin/advanced-access-manager/issues/153
 * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
 *              Fixed https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.2
 */
class AAM_Service_Route
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'api-route';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.route.enabled';

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
                    AAM_Backend_Feature_Main_Route::register();
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

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize API Route hooks
     *
     * @return void
     *
     * @since 6.7.0 https://github.com/aamplugin/advanced-access-manager/issues/153
     * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
     *              Fixed https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.0
     */
    protected function initializeHooks()
    {
        if (is_admin()) {
            add_filter('aam_settings_list_filter', function ($settings, $type) {
                if ($type === 'core') {
                    $settings = array_merge($settings, array(
                        'core.settings.xmlrpc' => array(
                            'title'       => __('XML-RPC WordPress API', AAM_KEY),
                            'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Remote procedure call (RPC) interface is used to manage WordPress website content and features. For more information check %sXML-RPC Support%s article.', 'b'), '<a href="https://codex.wordpress.org/XML-RPC_Support">', '</a>'),
                            'value'       => AAM_Core_Config::get('core.settings.xmlrpc', true)
                        ),
                        'core.settings.restful' => array(
                            'title'       => __('RESTful WordPress API', AAM_KEY),
                            'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('RESTful interface that is used to manage WordPress website content and features. For more information check %sREST API handbook%s.', 'b'), '<a href="https://developer.wordpress.org/rest-api/">', '</a>'),
                            'value'       => AAM_Core_Config::get('core.settings.restful', true)
                        )
                    ));
                }

                return $settings;
            }, 10, 2);
        }

        // Disable XML-RPC if needed
        add_filter('xmlrpc_enabled', function($enabled) {
            if (AAM_Core_Config::get('core.settings.xmlrpc', true) === false) {
                $enabled = false;
            }

            return $enabled;
        });

        // Disable RESTful API if needed
        add_filter(
            'rest_authentication_errors',
            function ($response) {
                if (!is_wp_error($response) && !AAM_Core_Config::get('core.settings.restful', true)) {
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
        add_filter('rest_pre_dispatch', array($this, 'authorizeRequest'), 1, 3);

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        );

        // Service fetch
        $this->registerService();
    }

    /**
     * Generate API Route policy statements
     *
     * @param array                     $policy
     * @param string                    $resource_type
     * @param array                     $options
     * @param AAM_Core_Policy_Generator $generator
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_Route::OBJECT_TYPE) {
            if (!empty($options)) {
                $normalized = array();
                foreach($options as $id => $effect) {
                    $normalized[str_replace('|', ':', $id)] = !empty($effect);
                }

                $policy['Statement'] = array_merge(
                    $policy['Statement'],
                    $generator->generateBasicStatements($normalized, 'Route')
                );
            }
        }

        return $policy;
    }

    /**
     * Authorize REST request
     *
     * Based on the matched route, check if it is disabled for current user
     *
     * @param WP_Error|null   $response
     * @param WP_REST_Server  $server
     * @param WP_REST_Request $request
     *
     * @return WP_Error|null
     *
     * @since 6.7.2 https://github.com/aamplugin/advanced-access-manager/issues/163
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.2
     */
    public function authorizeRequest($response, $server, $request)
    {
        $user    = AAM::getUser();
        $object  = $user->getObject('route');
        $matched = $request->get_route();
        $method  = $request->get_method();

        foreach (array_keys($server->get_routes()) as $route) {
            if ($route === $matched || preg_match('#^' . preg_quote($route) . '$#i', $matched)) {
                if ($object->isRestricted('restful', $route, $method)) {
                    $response = new WP_Error(
                        'rest_access_denied',
                        __('Access Denied', AAM_KEY),
                        array('status' => 401)
                    );
                    break;
                }
            }
        }

        return $response;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Route::bootstrap();
}