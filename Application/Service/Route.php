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
 * API Route service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Route
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.route.enabled';

    /**
     * Map of routes and resources
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $resources = array(
        'user' => array(
            '/wp/v2/users'
        )
    );

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
     * @access protected
     * @version 6.0.0
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

        //register API manager is applicable
        add_action('parse_request', array($this, 'registerRouteControllers'), 1);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function registerRouteControllers()
    {
        global $wp;

        if (!empty($wp->query_vars['rest_route'])) {
            // REST API action authorization. Triggered before call is dispatched
            add_filter(
                'rest_request_before_callbacks', array($this, 'beforeDispatch'), 10, 3
            );

            // Manage access to the RESTful endpoints
            add_filter('rest_pre_dispatch', array($this, 'authorizeRequest'), 1, 3);

            // Register additional RESTful resources if defined
            $this->registerAdditionalResources();
        }
    }

    /**
     * Authorize RESTful action before it is dispatched by RESTful Server
     *
     * @param mixed  $result
     * @param object $handler
     * @param object $request
     *
     * @return mixed
     *
     * @access public
     */
    public function beforeDispatch($result, $handler, $request)
    {
        if (empty($result)) {
            foreach ($this->resources as $res => $routes) {
                foreach ($routes as $regex) {
                    if (preg_match('#^' . $regex . '$#i', $request->get_route())) {
                        $class_name = 'AAM_Api_Rest_Resource_' . ucfirst($res);
                        $result    = $class_name::getInstance()->authorize($request);
                    }
                }
            }
        }

        return $result;
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
     * @access public
     */
    public function authorizeRequest($response, $server, $request)
    {
        $user    = AAM::getUser();
        $object  = $user->getObject('route');
        $matched = $request->get_route();
        $method  = $request->get_method();

        foreach (array_keys($server->get_routes()) as $route) {
            if ($route === $matched || preg_match("#^{$route}$#i", $matched)) {
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

    /**
     * Register additional endpoint resource to control
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function registerAdditionalResources()
    {
        // Register any additional endpoints with ConfigPress
        $additional = AAM_Core_Config::get('rest.manage.endpoint');

        if (!empty($additional) && is_array($additional)) {
            $this->resources = array_merge_recursive($this->resources, $additional);
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Route::bootstrap();
}