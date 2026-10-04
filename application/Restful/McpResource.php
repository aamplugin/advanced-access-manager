<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for MCP resources
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Restful_McpResource
{
    use AAM_Restful_ServiceTrait;

    /**
     * Permissions required to manage MCP resources
     *
     * @var array
     * @version 8.0.0
     */
    const PERMISSIONS = [ 'aam_manager', 'aam_manage_mcps' ];

    /**
     * Register collection and item routes for MCP resources
     *
     * @return void
     * @access protected
     * @version 8.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', function() {
            $this->_register_route('/mcp-resources', [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_items' ]
            ], self::PERMISSIONS);
            $this->_register_route('/mcp-resources', [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_items' ]
            ], self::PERMISSIONS);

            $server_id = [
                'type' => 'string',
                'required' => true,
                'description' => 'Registered MCP server ID',
                'validate_callback' => [ $this, 'validate_server' ]
            ];
            $resource = [
                'type' => 'string',
                'required' => true,
                'description' => 'Registered MCP resource ID',
                'validate_callback' => [ $this, 'validate_resource' ]
            ];
            $route = '/mcp-server/primitive/resource';
            $this->_register_route($route, [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_item' ],
                'args' => [ 'server_id' => $server_id, 'resource' => $resource ]
            ], self::PERMISSIONS);
            $this->_register_route($route, [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_item' ],
                'args' => [
                    'server_id' => $server_id,
                    'resource' => $resource,
                    'effect' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => [ 'allow', 'deny' ]
                    ]
                ]
            ], self::PERMISSIONS);
            $this->_register_route($route, [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_item' ],
                'args' => [ 'server_id' => $server_id, 'resource' => $resource ]
            ], self::PERMISSIONS);
        });
    }

    /**
     * Return registered MCP resources and their access rules
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
                'aam_rest_mcp_resource_access_list_filter',
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
     * Return one registered MCP resource
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
     * Set one MCP resource rule through the framework service
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
            $service->$method(
                $request->get_param('resource'),
                $request->get_param('server_id')
            );
            $result = $this->_get_item($request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset one MCP resource rule
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
                $request->get_param('resource'),
                $request->get_param('server_id')
            ) ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all MCP resource rules
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
     * Validate the server that owns this resource
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
     * Validate a registered resource within its server
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return bool|WP_Error
     * @access public
     * @version 8.0.0
     */
    public function validate_resource($value, $request)
    {
        $adapter = class_exists('\\WP\\MCP\\Core\\McpAdapter')
            ? \WP\MCP\Core\McpAdapter::instance() : null;
        $server = $adapter ? $adapter->get_server($request->get_param('server_id')) : null;

        if ($server) {
            foreach ($this->_get_resources($server) as $item) {
                if ($item->getUri() === $value) {
                    return true;
                }
            }
        }

        return new WP_Error('rest_invalid_param', 'MCP resource is not registered', [
            'status' => 400
        ]);
    }

    /**
     * Build a plain list of registered MCP resources
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
                $server_id = $server->get_server_id();
                foreach ($this->_get_resources($server) as $component) {
                    $id = $component->getUri();
                    $item = [
                        'name' => $component->getName(),
                        'server_id' => $server_id,
                        'description' => $component->getDescription(),
                        'effect' => $service->is_allowed($id, $server_id)
                            ? 'allow' : 'deny',
                        'customized' => $service->is_customized($id, $server_id)
                    ];
                    $item['uri'] = $id;
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * Find one registered MCP resource in its collection
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
            if ($item['uri'] === $request->get_param('resource')
                && $item['server_id'] === $request->get_param('server_id')) {
                return $item;
            }
        }

        throw new OutOfRangeException('MCP resource does not exist');
    }

    /**
     * List resources with the schema required by MCP Adapter 0.7 and newer.
     *
     * @param \WP\MCP\Core\McpServer $server
     *
     * @return array
     * @access private
     * @version 8.0.0
     */
    private function _get_resources($server)
    {
        $result = [];

        if (method_exists($server, 'get_schemas')) {
            $versions = \WP\McpSchema\Schemas::supportedVersions();
            $schema = $server->get_schemas()->forVersion(end($versions));

            $result = $server->get_resources($schema);
        }

        return $result;
    }

    /**
     * Get the framework MCP resource service for the requested access level
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_McpResources
     * @access private
     * @version 8.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->mcp_resources(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }
}
