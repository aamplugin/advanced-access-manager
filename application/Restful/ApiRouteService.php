<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the API route service
 *
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
 * @since 6.9.10 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Restful_ApiRouteService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
     * @since 6.9.10 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.13
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of routes
            $this->_register_route('/api-routes', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_routes'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get a route
            $this->_register_route('/api-route/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'API route unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update a route permission
            $this->_register_route('/api-route/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'API route unique ID',
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'is_restricted' => array(
                        'description' => 'Either route is restricted or not',
                        'type'        => 'boolean',
                        'default'     => true
                    )
                )
            ));

            // Delete a route permission
            $this->_register_route('/api-route/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'API route unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all routes' permissions
            $this->_register_route('/api-routes', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_routes'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all restrictions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function get_routes(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_route_list();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a route
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function get_route(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_route_by_id(
                intval($request->get_param('id'))
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update a route
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function update_route(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_route_permission(
                intval($request->get_param('id')),
                $request->get_param('is_restricted')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a route
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function delete_route(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_route_permission(
                intval($request->get_param('id'))
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all routes' permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function reset_routes(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Check if current user has access to the service
     *
     * @return bool
     *
     * @access public
     * @version 6.9.10
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
                && (current_user_can('aam_manage_routes')
                || current_user_can('aam_manage_api_routes'));
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_ApiRoutes
     *
     * @access private
     * @version 6.9.10
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::api_routes([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}