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
 * AAM JWT Network service
 *
 * @package AAM
 * @version 6.7.0
 */
class AAM_Service_JwtNetwork
{
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * DB cache option
     *
     * @version 6.7.0
     */
    const CACHE_DB_OPTION = 'aam_jwtnetwork_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.7.0
     */
    const FEATURE_FLAG = 'core.service.jwtnetwork.enabled';

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
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_JwtNetwork::register();
                }, 1);
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Multisite JWT Support', AAM_KEY),
                    'description' => __('JWT Token: WP Network overview add support for disposal of sites of an user.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 80);
        }

        // Hook that initialize the AAM UI part of the service
        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.6.0
     */
    protected function initializeHooks()
    {
        if (AAM::isAAM()) {
            /*
            add_action('aam_post_edit_user_modal_action', function () {
                if (current_user_can('aam_manage_jwt')) {
                    echo AAM_Backend_View::getInstance()->loadPartial('jwt-login-url');
                }
            });
            */
        }

        // Register API endpoint
        add_action('rest_api_init', array($this, 'registerAPI'));

        // Fetch specific claim from the JWT token if present
        add_filter('aam_get_jwt_claim', array($this, 'getJwtClaim'), 20, 2);

        // Service fetch
        $this->registerService();
    }

    /**
     * Register JWT RESTful API endpoints
     *
     * @return void
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.1
     */
    public function registerAPI()
    {
        // JWT token claim(s) dispatch to WP Network sites
        register_rest_route('aam/v2', '/jwt/dispatch', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'networkDispatch'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'jwt' => array(
                    'description' => __('JWT: Dispatch to network.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
    }

    /**
     * User dispatch to WP Network sites:
     *   1. JWT token of the WP_REST_request role gets validated if is administrator role
     *   2. User object is to be checked whenever WP_User with proper role, sites access is configured already in DB
     *   3. Returns creation status with user_id and roles
     *
     * Checked user_id will be used in JWT token claims with roles from external app
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function networkDispatch(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        if(is_null($jwt)) {
            $authHeader = $request->get_header('authorization');
            if(!is_null($authHeader) && strpos($authHeader, 'Bearer ') !== false) {
                $jwt = str_replace('Bearer ', '', $authHeader);
            }
        }

        $post = $request->get_json_params();

        $result = AAM_Core_Jwt_NetworkDispatch::getInstance()->adminUserNetworkDispatch($jwt, $post, $request);

        if ($result->hasDispatched === true) {
            $response = new WP_REST_Response($result);
        } else {
            $response = new WP_REST_Response(array(
                'code'   => 'rest_jwt_network_dispatch_failure',
                'reason' => $result->reason
            ), $result->status);
        }

        return $response;
    }

    /**
     * Revoke user capability on some site in the network
     *
     * @param int    $userId
     * @param int    $siteId
     *
     * @return bool
     *
     * @since 6.7.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.0
     */
    public function revokeUserSite($userId, $siteId)
    {
        $filtered = array();

        return update_user_option($userId, self::DB_OPTION, $filtered);
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_JwtNetwork::bootstrap();
}