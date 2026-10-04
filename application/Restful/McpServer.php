<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for MCP servers
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Restful_McpServer
{
    use AAM_Restful_ServiceTrait;

    /**
     * Permissions required to manage MCP servers
     *
     * @var array
     * @version 8.0.0
     */
    const PERMISSIONS = [ 'aam_manager', 'aam_manage_mcps' ];

    /**
     * Register MCP server collection and item routes
     *
     * @return void
     * @access protected
     * @version 8.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', function() {
            $this->_register_route('/mcp-servers', [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_items' ]
            ], self::PERMISSIONS);
            $this->_register_route('/mcp-servers', [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_items' ]
            ], self::PERMISSIONS);

            $resource = [
                'type' => 'string',
                'required' => true,
                'description' => 'Registered MCP server ID',
                'validate_callback' => [ $this, 'validate_server' ]
            ];
            $this->_register_route('/mcp-server', [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_item' ],
                'args' => [ 'resource' => $resource ]
            ], self::PERMISSIONS);
            $this->_register_route('/mcp-server', [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_item' ],
                'args' => [
                    'resource' => $resource,
                    'effect' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => [ 'allow', 'deny' ]
                    ]
                ]
            ], self::PERMISSIONS);
            $this->_register_route('/mcp-server', [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_item' ],
                'args' => [ 'resource' => $resource ]
            ], self::PERMISSIONS);
        });
    }

    /**
     * Return registered MCP servers and their access rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $result = apply_filters(
                'aam_rest_mcp_server_access_list_filter',
                $this->_list_items($request),
                $this->_determine_access_level($request)
            );
            $result = array_values($result);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Return one registered MCP server
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_item($request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set an MCP server access rule through the framework service
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function update_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $method = $request->get_param('effect') === 'allow' ? 'allow' : 'deny';
            $service->$method($request->get_param('resource'));
            $result = $this->_get_item($request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset one MCP server rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function reset_item(WP_REST_Request $request)
    {
        try {
            $result = [ 'success' => $this->_get_service($request)->reset(
                $request->get_param('resource')
            ) ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all MCP server rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     * @version 8.0.0
     */
    public function reset_items(WP_REST_Request $request)
    {
        try {
            $result = [ 'success' => $this->_get_service($request)->reset() ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Validate a registered MCP server ID
     *
     * @param string $value
     *
     * @return bool|WP_Error
     * @access public
     * @version 8.0.0
     */
    public function validate_server($value)
    {
        if (class_exists('\\WP\\MCP\\Core\\McpAdapter')
            && \WP\MCP\Core\McpAdapter::instance()->get_server($value)) {
            return true;
        }

        return new WP_Error('rest_invalid_param', 'MCP server is not registered', [
            'status' => 400
        ]);
    }

    /**
     * Build a plain list of registered MCP servers
     *
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     * @version 8.0.0
     */
    private function _list_items($request)
    {
        $items = [];
        if (class_exists('\\WP\\MCP\\Core\\McpAdapter')) {
            $service = $this->_get_service($request);
            foreach (\WP\MCP\Core\McpAdapter::instance()->get_servers() as $server) {
                $id = $server->get_server_id();
                $items[] = [
                    'id' => $id,
                    'name' => $server->get_server_name(),
                    'description' => $server->get_server_description(),
                    'route' => '/' . trim($server->get_server_route_namespace(), '/')
                        . '/' . trim($server->get_server_route(), '/'),
                    'effect' => $service->is_allowed($id) ? 'allow' : 'deny',
                    'customized' => $service->is_customized($id)
                ];
            }
        }

        return $items;
    }

    /**
     * Find a registered MCP server in the collection
     *
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     * @version 8.0.0
     */
    private function _get_item($request)
    {
        foreach ($this->_list_items($request) as $item) {
            if ($item['id'] === $request->get_param('resource')) {
                return $item;
            }
        }

        throw new OutOfRangeException('MCP server does not exist');
    }

    /**
     * Get the framework MCP server service for the requested access level
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_McpServers
     * @access private
     * @version 8.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->mcp_servers(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }
}
