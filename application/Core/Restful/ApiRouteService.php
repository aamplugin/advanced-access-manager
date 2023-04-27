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
 * @package AAM
 * @version 6.9.10
 */
class AAM_Core_Restful_ApiRouteService
{

    use AAM_Core_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.10
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of routes
            $this->_register_route('/api-route', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_routes'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Get a route
            $this->_register_route('/api-route/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('API route unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update a route permission
            $this->_register_route('/api-route/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('API route unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'is_restricted' => array(
                        'description' => __('Either route is restricted or not', AAM_KEY),
                        'type'        => 'boolean'
                    )
                )
            ));

            // Delete a route permission
            $this->_register_route('/api-route/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_route'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('API route unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all routes' permissions
            $this->_register_route('/api-route/reset', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'reset_routes'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
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
        $service = $this->_get_service($request);

        return rest_ensure_response($service->get_route_list());
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
        $service = $this->_get_service($request);

        try {
            $result = $service->get_route_by_id(
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
        $service = $this->_get_service($request);

        try {
            $result = $service->update_route_permission(
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
        $service = $this->_get_service($request);

        try {
            $result = $service->delete_route_permission(
                intval($request->get_param('id'))
            );
        } catch (UnderflowException $e) {
            $result = $this->_prepare_error_response($e, 'rest_not_found', 404);
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
        $service = $this->_get_service($request);

        try {
            $result = $service->reset_routes();
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
        return AAM_Framework_Manager::api_routes(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );
    }

}