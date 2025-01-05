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
 * @version 7.0.0
 */
class AAM_Restful_ApiRouteService
{

    use AAM_Restful_ServiceTrait;

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
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of routes
            $this->_register_route('/api-routes', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get a route
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description'       => 'Based64 encoded API route + method',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value) {
                            return $this->_validate_base64($value);
                        }
                    )
                )
            ));

            // Update a route permission
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description'       => 'Based64 encoded API route + method',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value) {
                            return $this->_validate_base64($value);
                        }
                    ),
                    'effect' => array(
                        'description' => 'Either route is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    )
                )
            ));

            // Delete a route permission
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description'       => 'Based64 encoded API route + method',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value) {
                            return $this->_validate_base64($value);
                        }
                    )
                )
            ));

            // Reset all routes' permissions
            $this->_register_route('/api-routes', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all API routes with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_items();
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
     * @version 7.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $id      = $request->get_param('id');

            // Unserialize the ID - extract HTTP method & endpoint
            list($method, $endpoint) = explode(' ', base64_decode($id));

            $result = $service->get_item($endpoint, $method);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update a route permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $id      = $request->get_param('id');
            $effect  = $request->get_param('effect');

            // Unserialize the ID - extract HTTP method & endpoint
            list($method, $endpoint) = explode(' ', base64_decode($id));

            if ($effect === 'allow') {
                $service->allow($endpoint, $method);
            } else {
                $service->deny($endpoint, $method);
            }

            $result = $service->item($endpoint, $method);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a route permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_item_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $id      = $request->get_param('id');

            // Unserialize the ID - extract HTTP method & endpoint
            list($method, $endpoint) = explode(' ', base64_decode($id));

            $service->reset($endpoint, $method);

            $result = [ 'success' => true ];
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
     * @version 7.0.0
     */
    public function reset_permissions(WP_REST_Request $request)
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
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_api_routes');
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_ApiRoutes
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->api_routes([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

    /**
     * Validate that the string is valid base64 encoded
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_base64($value)
    {
        $response = true;

        if (!AAM::api()->misc->is_base64_encoded($value)) {
            $response = new WP_Error(
                'rest_invalid_param',
                'Invalid ID',
                [ 'status'  => 400 ]
            );
        }

        return $response;
    }

}