<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API service that must be on at all time
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Mu
{

    use AAM_Restful_ServiceTrait;

    /**
     * Construct
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Covering the bug in WordPress core that does not set correct user local
        add_filter('rest_pre_dispatch', function($r, $_, $request) {
            global $wp_locale_switcher;

            if (strpos($request->get_route(), '/aam/') !== false) {
                $user_id = get_current_user_id();

                if (function_exists('switch_to_user_locale')) {
                    switch_to_user_locale($user_id);
                } elseif (is_a($wp_locale_switcher, 'WP_Locale_Switcher')) {
                    $wp_locale_switcher->switch_to_locale(
                        get_user_locale($user_id), $user_id
                    );
                }
            }

            return $r;
        }, 10, 3);

        // Few services always available to support AAM UI
        AAM_Restful_Roles::bootstrap();
        AAM_Restful_Users::bootstrap();
        AAM_Restful_Configs::bootstrap();
        AAM_Restful_Settings::bootstrap();

        // Register API endpoint
        add_action('rest_api_init', function() {
            // Reset AAM
            $this->_register_route('/reset', [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'reset' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ], false, 'aam/v2');

            // Export AAM settings, configurations & roles
            $this->_register_route('/export', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'export' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ], false, 'aam/v2');

            // Import AAM settings, configurations & roles
            $this->_register_route('/import', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'import' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ], false, 'aam/v2');
        });

        // Get currently managed "Access Level"
        add_filter(
            'aam_rest_get_access_level_filter',
            [ $this, 'get_access_level' ],
            10,
            2
        );

        // Handle common REST errors
        add_filter(
            'aam_rest_get_error_response_filter',
            [ $this, 'get_error_response' ],
            10,
            4
        );

        // Register a common AAM, access level aware RESTful API endpoint
        add_action('aam_rest_register_route', [ $this, 'register_route' ], 10, 4);
    }

    /**
     * Reset all AAM settings
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        return rest_ensure_response([
            'success' => AAM_Service_Core::get_instance()->reset()
        ]);
    }

    /**
     * Export all AAM settings
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function export()
    {
        return rest_ensure_response(AAM_Service_Core::get_instance()->export());
    }

    /**
     * Export all AAM settings
     *
     * @param WP_REST_Request $request
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function import($request)
    {
        return rest_ensure_response([
            'success' => AAM_Service_Core::get_instance()->import(
                $request->get_param('dataset')
            )
        ]);
    }

     /**
     * Check if current user has access to the service
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && AAM::api()->misc->is_super_admin();
    }

    /**
     * Get current access level
     *
     * @param null|object     $access_level
     * @param WP_REST_Request $request
     *
     * @return null|object
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level($access_level, $request)
    {
        if (is_null($access_level)) {
            $access_level = $this->_determine_access_level($request);
        }

        return $access_level;
    }

    /**
     * Get RESTful error response
     *
     * @param mixed     $response
     * @param Exception $exception
     * @param string    $rest_code
     * @param int       $http_status
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_error_response(
        $response, $exception, $rest_code, $http_status
    ) {
        if (is_null($response)) {
            $response = $this->_prepare_error_response(
                $exception, $rest_code, $http_status
            );
        }

        return $response;
    }

    /**
     * Register AAM standard RESTful API route
     *
     * @param string $route
     * @param array  $args
     * @param bool   $access_level_aware
     * @param string $ns
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function register_route(
        $route, $args, $access_level_aware = true, $ns = null
    ) {
        $this->_register_route($route, $args, $access_level_aware, $ns);
    }

}