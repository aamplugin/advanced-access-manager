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
 * Not Found (404) Redirect RESTful API test
 */
final class NotFoundRedirectTest extends TestCase
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
            '/aam/v2/redirect/not-found',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ 'type' => 'default' ], $result->get_data());
    }

    /**
     * Test set redirect
     *
     * @return void
     */
    public function testSetRedirect()
    {
        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'POST',
            '/aam/v2/redirect/not-found',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ],
                'post_params' => [
                    'type'         => 'url_redirect',
                    'redirect_url' => site_url('/support')
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([
            'type'         => 'url_redirect',
            'redirect_url' => '/support'
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
            '/aam/v2/redirect/not-found',
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