<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM\UnitTest\Utility\TestCase;

/**
 * Access Denied Redirect RESTful API test
 */
final class AccessDeniedRedirectTest extends TestCase
{

    /**
     * Test get redirects endpoint
     *
     * @return void
     */
    public function testGetRedirect()
    {
        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'GET',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber',
                    'area'         => 'frontend'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ 'type' => 'default' ], $result->get_data());

        // Test that we can get all areas at once
        $result = $server->dispatch($this->prepareRestRequest(
            'GET',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([
            'frontend' => [ 'type' => 'default' ],
            'backend'  => [ 'type' => 'default' ],
            'api'      => [ 'type' => 'default' ],
        ], $result->get_data());
    }

    /**
     * Test set redirect
     *
     * @return void
     */
    public function testSetRedirect()
    {
        $page_a = $this->createPost([ 'post_type' => 'page' ]);
        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'POST',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ],
                'post_params' => [
                    'area'             => 'frontend',
                    'type'             => 'page_redirect',
                    'redirect_page_id' => $page_a
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([
            'type'             => 'page_redirect',
            'redirect_page_id' => $page_a
        ], $result->get_data());
    }

    /**
     * Test reset redirect
     *
     * @return void
     */
    public function testResetRedirect()
    {
        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'DELETE',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber',
                    'area'         => 'backend'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ 'success' => true ], $result->get_data());

        // Test resetting for all areas
        $result = $server->dispatch($this->prepareRestRequest(
            'DELETE',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ 'success' => true ], $result->get_data());
    }

}