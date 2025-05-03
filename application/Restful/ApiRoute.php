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
class AAM_Restful_ApiRoute
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_api_routes'
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of routes
            $this->_register_route('/api-routes', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items')
            ), self::PERMISSIONS);

            // Get a route
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args'     => array(
                    'id' => array(
                        'description'       => 'Based64 encoded API route + method',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value) {
                            return $this->_validate_base64($value);
                        }
                    )
                )
            ), self::PERMISSIONS);

            // Update a route permission
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item_permissions'),
                'args'     => array(
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
            ), self::PERMISSIONS);

            // Delete a route permission
            $this->_register_route('/api-route/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_item_permissions'),
                'args'     => array(
                    'id' => array(
                        'description'       => 'Based64 encoded API route + method',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value) {
                            return $this->_validate_base64($value);
                        }
                    )
                )
            ), self::PERMISSIONS);

            // Reset all routes' permissions
            $this->_register_route('/api-routes', array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_permissions')
            ), self::PERMISSIONS);
        });
    }

    /**
     * Get list of all API routes with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_route_list($this->_get_service($request));
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $result = $this->_find_route_by_id(
                $this->_get_service($request),
                $request->get_param('id')
            );

            if (empty($result)) {
                throw new OutOfRangeException('Route does not exist');
            }
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
     * @access public
     *
     * @version 7.0.0
     */
    public function update_item_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $id      = $request->get_param('id');
            $effect  = $request->get_param('effect');

            if ($effect === 'allow') {
                $service->allow(base64_decode($id));
            } else {
                $service->deny(base64_decode($id));
            }

            $result = $this->_find_route_by_id($service, $id);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_item_permissions(WP_REST_Request $request)
    {
        try {
            $result  = [
                'success' => $this->_get_service($request)->reset(
                    base64_decode($request->get_param('id'))
                )
            ];
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => $this->_get_service($request)->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get complete list of routes
     *
     * @param AAM_Framework_Service_ApiRoutes $service
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_route_list($service)
    {
        $result = [];

        // Iterating over the list of all registered API routes and compile the
        // list
        foreach (rest_get_server()->get_routes() as $endpoint => $handlers) {
            $methods = [];

            foreach ($handlers as $handler) {
                $methods = array_merge(
                    $methods, array_keys($handler['methods'])
                );
            }

            foreach (array_unique($methods) as $method) {
                array_push($result, [
                    'endpoint'      => strtolower($endpoint),
                    'method'        => strtoupper($method),
                    'is_restricted' => $service->is_denied($method . ' ' . $endpoint),
                    'id'            => base64_encode(strtolower(
                        "{$method} {$endpoint}"
                    ))
                ]);
            }
        }

        return $result;
    }

    /**
     * Find API route by ID
     *
     * @param AAM_Framework_Service_ApiRoute $service
     * @param string                         $id
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _find_route_by_id($service, $id)
    {
        $routes = $this->_get_route_list($service);
        $found  = array_filter($routes, function($r) use ($id) {
            return $r['id'] === $id;
        });

        return !empty($found) ? array_shift($found) : null;
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_ApiRoutes
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->api_routes(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

    /**
     * Validate that the string is valid base64 encoded
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     * @access private
     *
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