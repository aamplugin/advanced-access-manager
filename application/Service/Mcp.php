<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * MCP Adapter access service.
 *
 * @package AAM
 * 
 * @version 8.0.0
 */
class AAM_Service_Mcp
{
    use AAM_Service_BaseTrait;

    /**
     * Register the MCP REST API and adapter checks.
     *
     * @version 8.0.0
     */
    protected function __construct()
    {
        AAM_Restful_McpServer::bootstrap();
        AAM_Restful_McpTool::bootstrap();
        AAM_Restful_McpResource::bootstrap();
        AAM_Restful_McpPrompt::bootstrap();

        add_action('aam_initialize_ui_action', function () {
            AAM_Backend_Feature_Main_Ability::register();
        });

        // Control MCP primitives access and visibility
        add_filter('mcp_adapter_tools_list', function ($tools, $server) {
            return $this->_filter_tools($tools, $server);
        }, 10, 2);

        add_filter('mcp_adapter_resources_list', function ($resources, $server) {
            return $this->_filter_resources($resources, $server,);
        }, 10, 2);

        add_filter('mcp_adapter_prompts_list', function ($prompts, $server) {
            return $this->_filter_prompts($prompts, $server);
        }, 10, 2);

        // Control MCP privimites' execution
        add_filter('mcp_adapter_pre_tool_call', function($args, $name, $_, $server) {
            return $this->_check_tool_access($args, $name, $server);
        }, 10, 4);
        add_filter('mcp_adapter_pre_resource_read', function($args, $uri, $_, $server) {
            return $this->_check_resource_access($args, $uri, $server);
        }, 10, 4);
        add_filter('mcp_adapter_pre_prompt_get', function($args, $name, $prompt, $server) {
            return $this->_check_prompt_access($args, $name, $server);
        }, 10, 4);

        // Control access to the entire MCP server
        add_filter('rest_pre_dispatch', function($result, $_, $request) {
            return $this->_check_mcp_http_route($result, $request);
        }, 10, 3);
    }

    /**
     * Hide denied tools from the MCP catalog.
     *
     * @param array $tools
     * @param \WP\MCP\Core\McpServer $server
     *
     * @return array
     * @access private
     * 
     * @version 8.0.0
     */
    private function _filter_tools($tools, $server)
    {
        $tools = array_filter($tools, function ($tool) use ($server) {
            return AAM::api()->mcp_tools()->is_allowed(
                $tool, 
                $server->get_server_id()
            );
        });

        return array_values($tools); // reset array index
    }

    /**
     * Hide denied resources from the MCP catalog.
     *
     * @param array                  $resources
     * @param \WP\MCP\Core\McpServer $server
     *
     * @return array
     * @access private
     * 
     * @version 8.0.0
     */
    private function _filter_resources($resources, $server)
    {
        $resources = array_filter($resources, function ($res) use ($server) {
            return AAM::api()->mcp_resources()->is_allowed(
                $res,
                $server->get_server_id()
            );
        });

        return array_values($resources); // reset array index
    }

    /**
     * Hide denied prompts from the MCP catalog.
     *
     * @param array                  $prompts
     * @param \WP\MCP\Core\McpServer $server
     *
     * @return array
     * @access private
     * 
     * @version 8.0.0
     */
    private function _filter_prompts($prompts, $server)
    {
        $prompts = array_filter($prompts, function ($res) use ($server) {
            return AAM::api()->mcp_prompts()->is_allowed(
                $res, 
                $server->get_server_id()
            );
        });

        return array_values($prompts); // reset array index
    }

    /**
     * Block a tool call if the tool or its server is restricted.
     *
     * @param mixed  $args
     * @param string $name
     * @param object $server
     *
     * @return mixed
     * @access private
     * 
     * @version 8.0.0
     */
    private function _check_tool_access($args, $name, $server)
    {
        $server_id        = $server->get_server_id();
        $is_server_denied = AAM::api()->mcp_servers()->is_denied($server_id);
        $is_tool_denied   = AAM::api()->mcp_tools()->is_denied($name, $server_id);

        if ($is_server_denied || $is_tool_denied) {
            return AAM_Service_AccessDeniedRedirect::denied_error(
                'aam_mcp_denied', 
                __('This MCP tool is restricted by AAM.', 'advanced-access-manager')
            );
        }

        return $args;
    }

    /**
     * Deny a resource read when its server or URI is restricted.
     *
     * @param mixed  $args
     * @param string $uri
     * @param object $server
     *
     * @return mixed
     * @access private
     * 
     * @version 8.0.0
     */
    private function _check_resource_access($args, $uri, $server)
    {
        $server_id        = $server->get_server_id();
        $is_server_denied = AAM::api()->mcp_servers()->is_denied($server_id);
        $is_res_denied    = AAM::api()->mcp_resources()->is_denied($uri, $server_id);

        return $is_server_denied || $is_res_denied
            ? AAM_Service_AccessDeniedRedirect::denied_error(
                'aam_mcp_denied',
                __('This MCP resource is restricted by AAM.', 'advanced-access-manager')
            )
            : $args;
    }

    /**
     * Deny a prompt request when its server or prompt is restricted.
     *
     * @param mixed  $args
     * @param string $name
     * @param object $server
     *
     * @return mixed
     * @access private
     * 
     * @version 8.0.0
     */
    private function _check_prompt_access($args, $name, $server)
    {
        $server_id        = $server->get_server_id();
        $is_server_denied = AAM::api()->mcp_servers()->is_denied($server_id);
        $is_prompt_denied = AAM::api()->mcp_prompts()->is_denied($name, $server_id);

        return $is_server_denied || $is_prompt_denied
            ? AAM_Service_AccessDeniedRedirect::denied_error(
                'aam_mcp_denied',
                __('This MCP prompt is restricted by AAM.', 'advanced-access-manager')
            )
            : $args;
    }

    /**
     * Reject HTTP traffic to a restricted MCP server before dispatch.
     *
     * @param mixed           $result
     * @param WP_REST_Request $request
     *
     * @return mixed
     * @access private
     * 
     * @version 8.0.0
     */
    private function _check_mcp_http_route($result, $request)
    {
        if (!is_wp_error($result) && class_exists('\\WP\\MCP\\Core\\McpAdapter')) {
            foreach (\WP\MCP\Core\McpAdapter::instance()->get_servers() as $server) {
                $route  = '/' . trim($server->get_server_route_namespace(), '/');
                $route .= '/' . trim($server->get_server_route(), '/');

                if (
                    rtrim($request->get_route(), '/') === rtrim($route, '/')
                    && AAM::api()->mcp_servers()->is_denied( $server->get_server_id())
                ) {
                    return AAM_Service_AccessDeniedRedirect::denied_error(
                        'aam_mcp_denied',
                        __('This MCP server is restricted by AAM.', 'advanced-access-manager'),
                        [ 'status' => 403 ]
                    );
                }
            }
        }

        return $result;
    }

}
