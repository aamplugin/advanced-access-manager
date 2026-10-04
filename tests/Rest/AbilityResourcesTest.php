<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM;
use AAM\UnitTest\Utility\TestCase;

final class AbilityResourcesTest extends TestCase
{
    /**
     * MCP server and primitive routes belong to separate REST classes.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testMcpRoutesHaveDistinctHandlers(): void
    {
        $routes = rest_get_server()->get_routes();
        foreach ([
            '/mcp-server' => \AAM_Restful_McpServer::class,
            '/mcp-server/primitive/tool' => \AAM_Restful_McpTool::class,
            '/mcp-server/primitive/resource' => \AAM_Restful_McpResource::class,
            '/mcp-server/primitive/prompt' => \AAM_Restful_McpPrompt::class
        ] as $path => $class) {
            $handlers = $routes['/aam/v2' . $path];
            foreach ($handlers as $handler) {
                if (isset($handler['callback'])) {
                    $this->assertInstanceOf($class, $handler['callback'][0], $path);
                }
            }
        }
    }

    public function testDistinctAbilityAndMcpRoutes(): void
    {
        $server = rest_get_server();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];

        foreach ([ '/abilities', '/mcp-servers', '/mcp-tools', '/mcp-resources', '/mcp-prompts' ] as $path) {
            $result = $server->dispatch($this->prepareRestRequest('GET', '/aam/v2' . $path, [
                'query_params' => $query
            ]));
            $this->assertSame(200, $result->get_status(), $path);
            $this->assertIsArray($result->get_data(), $path);
            $this->assertSame(array_values($result->get_data()), $result->get_data(), $path);

            $reset = $server->dispatch($this->prepareRestRequest('DELETE', '/aam/v2' . $path, [
                'query_params' => $query
            ]));
            $this->assertSame(200, $reset->get_status(), $path . ' DELETE');
        }

        foreach ([
            '/ability', '/mcp-server', '/mcp-server/primitive/tool',
            '/mcp-server/primitive/resource', '/mcp-server/primitive/prompt'
        ] as $path) {
            $result = $server->dispatch($this->prepareRestRequest('GET', '/aam/v2' . $path, [
                'query_params' => array_merge($query, [
                    'resource' => 'unregistered-resource',
                    'server_id' => 'unregistered-server'
                ])
            ]));
            $this->assertSame(400, $result->get_status(), $path);

            $updated = $server->dispatch($this->prepareRestRequest('PATCH', '/aam/v2' . $path, [
                'query_params' => array_merge($query, [
                    'resource' => 'unregistered-resource',
                    'server_id' => 'unregistered-server'
                ]),
                'post_params' => [ 'effect' => 'deny' ]
            ]));
            $this->assertSame(400, $updated->get_status(), $path . ' PATCH');

            $reset = $server->dispatch($this->prepareRestRequest('DELETE', '/aam/v2' . $path, [
                'query_params' => array_merge($query, [
                    'resource' => 'unregistered-resource',
                    'server_id' => 'unregistered-server'
                ])
            ]));
            $this->assertSame(400, $reset->get_status(), $path . ' DELETE');
        }
    }

    /**
     * Collection resets affect only their matching MCP resource.
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testMcpCollectionResetsAreIndependent(): void
    {
        $level = AAM::api()->role('subscriber');
        $servers = AAM::api()->mcp_servers($level);
        $tools = AAM::api()->mcp_tools($level);
        $resources = AAM::api()->mcp_resources($level);
        $prompts = AAM::api()->mcp_prompts($level);
        $this->assertTrue($servers->deny('unit-server'));
        $this->assertTrue($tools->deny('unit-tool', 'unit-server'));
        $this->assertTrue($resources->deny('https://example.com/item', 'unit-server'));
        $this->assertTrue($prompts->deny('unit-prompt', 'unit-server'));

        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];
        $server = rest_get_server();
        $server_reset = $server->dispatch($this->prepareRestRequest(
            'DELETE', '/aam/v2/mcp-servers', [ 'query_params' => $query ]
        ));
        $this->assertSame(200, $server_reset->get_status());
        $this->assertTrue($server_reset->get_data()['success']);
        $this->assertNull($level->get_resource('mcp_server')->get_permission(
            'unit-server', 'access'
        ));
        $this->assertSame('deny', $level->get_resource('mcp_tool')->get_permission(
            (object) [ 'name' => 'unit-tool', 'server_id' => 'unit-server' ],
            'access'
        )['effect']);

        $tool_reset = $server->dispatch($this->prepareRestRequest(
            'DELETE', '/aam/v2/mcp-tools', [ 'query_params' => $query ]
        ));
        $this->assertSame(200, $tool_reset->get_status());
        $this->assertTrue($tool_reset->get_data()['success']);
        $this->assertNull($level->get_resource('mcp_tool')->get_permission(
            (object) [ 'name' => 'unit-tool', 'server_id' => 'unit-server' ],
            'access'
        ));

        foreach ([
            [ '/mcp-resources', 'mcp_resource', (object) [
                'uri' => 'https://example.com/item', 'server_id' => 'unit-server'
            ] ],
            [ '/mcp-prompts', 'mcp_prompt', (object) [
                'name' => 'unit-prompt', 'server_id' => 'unit-server'
            ] ]
        ] as [ $path, $type, $identifier ]) {
            $this->assertSame('deny', $level->get_resource($type)
                ->get_permission($identifier, 'access')['effect']);
            $reset = $server->dispatch($this->prepareRestRequest(
                'DELETE', '/aam/v2' . $path, [ 'query_params' => $query ]
            ));
            $this->assertSame(200, $reset->get_status());
            $this->assertTrue($reset->get_data()['success']);
            $this->assertNull($level->get_resource($type)
                ->get_permission($identifier, 'access'));
        }
    }

    /**
     * Item writes use the framework services and return current rule state.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testRegisteredMcpServerAndToolRules(): void
    {
        $server = rest_get_server();
        $registered = \WP\MCP\Core\McpAdapter::instance()->get_servers();
        $this->assertNotEmpty($registered);
        $mcp_server = reset($registered);
        $server_id = $mcp_server->get_server_id();
        if (method_exists($mcp_server, 'get_schemas')) {
            $versions = \WP\McpSchema\Schemas::supportedVersions();
            $schema = $mcp_server->get_schemas()->forVersion(end($versions));
            $tools = $mcp_server->get_tools($schema);
        } else {
            $tools = $mcp_server->get_tools();
        }
        $this->assertNotEmpty($tools);
        $tool = reset($tools)->getName();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];
        $level = AAM::api()->role('subscriber');

        foreach ([
            [ '/mcp-server', $server_id, null, AAM::api()->mcp_servers($level) ],
            [ '/mcp-server/primitive/tool', $tool, $server_id,
                AAM::api()->mcp_tools($level) ]
        ] as [ $path, $resource, $owner, $service ]) {
            $params = $query + [ 'resource' => $resource ];
            if ($owner) {
                $params['server_id'] = $owner;
            }
            $saved = $server->dispatch($this->prepareRestRequest(
                'PATCH', '/aam/v2' . $path, [
                    'query_params' => $params,
                    'post_params' => [ 'effect' => 'deny' ]
                ]
            ));
            $this->assertSame(200, $saved->get_status(), $path);
            $this->assertSame('deny', $saved->get_data()['effect'], $path);
            $this->assertTrue($owner
                ? $service->is_denied($resource, $owner)
                : $service->is_denied($resource));

            $reset = $server->dispatch($this->prepareRestRequest(
                'DELETE', '/aam/v2' . $path, [ 'query_params' => $params ]
            ));
            $this->assertSame(200, $reset->get_status(), $path);
            $this->assertTrue($reset->get_data()['success'], $path);
        }
    }
}
