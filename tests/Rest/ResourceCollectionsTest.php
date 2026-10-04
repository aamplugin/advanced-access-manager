<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM\UnitTest\Utility\TestCase;

/**
 * Read contracts for resource collection endpoints
 *
 * @package AAM
 * @version 8.0.0
 */
final class ResourceCollectionsTest extends TestCase
{
    /**
     * Each enabled collection returns a successful response for a valid role.
     *
     * @return void
     * @access public
     */
    public function testResourceCollectionsCanBeRead(): void
    {
        $server = rest_get_server();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];

        foreach ([
            '/backend-menu', '/admin-toolbar', '/metaboxes', '/widgets',
            '/api-routes', '/urls', '/policies', '/post_types', '/taxonomies',
            '/terms', '/posts', '/configs', '/identity/roles', '/identity/users'
        ] as $path) {
            $params = $query;
            if ($path === '/terms') {
                $params['taxonomy'] = 'category';
            } elseif ($path === '/posts') {
                $params['post_type'] = 'post';
            }
            $response = $server->dispatch($this->prepareRestRequest(
                'GET', '/aam/v2' . $path, [ 'query_params' => $params ]
            ));
            $this->assertSame(200, $response->get_status(), $path . ': '
                . wp_json_encode($response->get_data()));
        }
    }

    /**
     * Bulk reset endpoints clear the selected role without a route error.
     *
     * @return void
     * @access public
     */
    public function testResourceCollectionsCanBeReset(): void
    {
        $server = rest_get_server();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];

        foreach ([
            '/backend-menu', '/admin-toolbar', '/metaboxes', '/widgets',
            '/api-routes', '/urls', '/policies'
        ] as $path) {
            $response = $server->dispatch($this->prepareRestRequest(
                'DELETE', '/aam/v2' . $path, [ 'query_params' => $query ]
            ));
            $this->assertSame(200, $response->get_status(), $path . ': '
                . wp_json_encode($response->get_data()));
            $this->assertTrue($response->get_data()['success'], $path);
        }
    }

    /**
     * Unknown resource IDs are rejected by individual item endpoints.
     *
     * @return void
     * @access public
     */
    public function testUnknownResourceIdsAreRejected(): void
    {
        $server = rest_get_server();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];

        foreach ([
            '/backend-menu/unit_missing', '/admin-toolbar/unit_missing',
            '/metabox/unit_missing', '/widget/unit_missing',
            '/api-route/unit_missing'
        ] as $path) {
            $response = $server->dispatch($this->prepareRestRequest(
                'GET', '/aam/v2' . $path, [ 'query_params' => $query ]
            ));
            $this->assertContains($response->get_status(), [ 400, 404 ],
                $path . ': ' . wp_json_encode($response->get_data()));
        }
    }
}
