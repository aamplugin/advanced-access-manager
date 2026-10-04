<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM;
use AAM\UnitTest\Utility\TestCase;

/**
 * MCP adapter catalog and operation checks for each primitive.
 *
 * @package AAM
 * @version 8.0.0
 */
final class McpRuntimeTest extends TestCase
{
    /**
     * Restricted tools, resources, and prompts are hidden and cannot run.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testPrimitiveCatalogAndOperationChecks(): void
    {
        $user_id = $this->createUser();
        wp_set_current_user($user_id);
        $server = new class {
            public function get_server_id() { return 'test-mcp-server'; }
        };
        $tool = new class {
            public function getName() { return 'test-tool'; }
        };
        $resource = new class {
            public function getUri() { return 'https://example.com/private'; }
        };
        $prompt = new class {
            public function getName() { return 'test-prompt'; }
        };
        $service = \AAM_Service_Mcp::bootstrap();
        $this->assertTrue(AAM::api()->mcp_tools()->deny('test-tool', 'test-mcp-server'));
        $this->assertTrue(AAM::api()->mcp_resources()->deny('https://example.com/private', 'test-mcp-server'));
        $this->assertTrue(AAM::api()->mcp_prompts()->deny('test-prompt', 'test-mcp-server'));

        $this->assertSame([], apply_filters('mcp_adapter_tools_list', [ $tool ], $server));
        $this->assertSame([], apply_filters('mcp_adapter_resources_list', [ $resource ], $server));
        $this->assertSame([], apply_filters('mcp_adapter_prompts_list', [ $prompt ], $server));
        $this->assertInstanceOf(\WP_Error::class,
            apply_filters('mcp_adapter_pre_tool_call', [], 'test-tool', $tool, $server));
        $this->assertInstanceOf(\WP_Error::class,
            apply_filters('mcp_adapter_pre_resource_read', [],
                'https://example.com/private', $resource, $server));
        $this->assertInstanceOf(\WP_Error::class,
            apply_filters('mcp_adapter_pre_prompt_get', [], 'test-prompt',
                $prompt, $server));
    }

    /**
     * A denied MCP server blocks its HTTP endpoint before dispatch.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testDeniedServerBlocksHttpRoute(): void
    {
        $user_id = $this->createUser();
        wp_set_current_user($user_id);
        rest_get_server();
        $servers = \WP\MCP\Core\McpAdapter::instance()->get_servers();
        $this->assertNotEmpty($servers);
        $server = reset($servers);
        $server_id = $server->get_server_id();
        $route = '/' . trim($server->get_server_route_namespace(), '/')
            . '/' . trim($server->get_server_route(), '/');
        $this->assertTrue(AAM::api()->mcp_servers()->deny($server_id));

        try {
            $result = apply_filters('rest_pre_dispatch', null,
                rest_get_server(), new \WP_REST_Request('POST', $route));
            $this->assertInstanceOf(\WP_Error::class, $result);
        } finally {
            AAM::api()->mcp_servers()->reset($server_id);
        }
    }
}
