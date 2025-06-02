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
    use AAM_Service_BaseTrait;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        // Register RESTful API endpoints
        AAM_Restful_ApiRoute::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize API Route hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_ApiRoute::register();
            });
        }

        // Register API manager is applicable
        add_filter('rest_pre_dispatch', function($result, $_, $request) {
            return $this->_rest_pre_dispatch($result, $request);
        }, PHP_INT_MAX, 3);
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
     * @access public
     *
     * @version 7.0.0
     */
    private function _rest_pre_dispatch($response, $request)
    {
        if (!is_wp_error($response)) {
            if (AAM::api()->api_routes()->is_denied($request)) {
                $response = new WP_Error(
                    'rest_access_denied',
                    __('Access Denied', 'advanced-access-manager'),
                    array('status' => 401)
                );
            }
        }

        return $response;
    }

}