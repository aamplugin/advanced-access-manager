<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM\UnitTest\Utility\TestCase;
use WP_REST_Request;

/**
 * Core REST route registration and authorization contracts
 *
 * @package AAM
 * @version 8.0.0
 */
final class RouteContractsTest extends TestCase
{
    /**
     * Every registered AAM route has callable handlers and permission checks.
     *
     * @return void
     * @access public
     */
    public function testAllRegisteredRoutesHaveHandlersAndPermissionCallbacks(): void
    {
        $routes = rest_get_server()->get_routes();
        $matched = 0;

        foreach ($routes as $path => $handlers) {
            if (strpos($path, '/aam/v2/') !== 0) {
                continue;
            }

            ++$matched;
            foreach ($handlers as $handler) {
                if (!isset($handler['callback'])) {
                    continue;
                }

                $this->assertIsCallable($handler['callback'], $path);
                $this->assertIsCallable($handler['permission_callback'], $path);
                $this->assertNotEmpty($handler['methods'], $path);
            }
        }

        $this->assertGreaterThan(35, $matched);
        foreach ([
            '/aam/v2/preload', '/aam/v2/settings', '/aam/v2/roles',
            '/aam/v2/users', '/aam/v2/capabilities', '/aam/v2/abilities',
            '/aam/v2/mcp-servers', '/aam/v2/mcp-tools',
            '/aam/v2/mcp-server', '/aam/v2/mcp-server/primitive/tool',
            '/aam/v2/mcp-server/primitive/resource',
            '/aam/v2/mcp-server/primitive/prompt', '/aam/v2/backend-menu',
            '/aam/v2/admin-toolbar', '/aam/v2/metaboxes', '/aam/v2/widgets'
        ] as $path) {
            $this->assertArrayHasKey($path, $routes);
        }
    }

    /**
     * Private management collections reject unauthenticated callers.
     *
     * @return void
     * @access public
     */
    public function testManagementRoutesRequireAuthentication(): void
    {
        wp_set_current_user(0);
        $server = rest_get_server();

        foreach ([
            '/aam/v2/preload', '/aam/v2/settings', '/aam/v2/roles',
            '/aam/v2/users', '/aam/v2/capabilities', '/aam/v2/abilities',
            '/aam/v2/mcp-servers', '/aam/v2/mcp-tools'
        ] as $path) {
            $request = new WP_REST_Request('GET', $path);
            if ($path === '/aam/v2/preload') {
                $request->set_param('access_level', 'role');
                $request->set_param('role_id', 'subscriber');
            }
            $response = $server->dispatch($request);
            $this->assertContains($response->get_status(), [ 401, 403 ], $path);
        }
    }
}
