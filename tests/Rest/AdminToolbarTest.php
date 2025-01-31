<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_AdminToolbar;

/**
 * Admin Toolbar RESTful API test
 */
final class AdminToolbarTest extends TestCase
{

    /**
     * Test get entire admin toolbar
     *
     * @return void
     */
    public function testGetAllItem()
    {
        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'GET',
            '/aam/v2/service/admin-toolbar',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ ], $result->get_data());
    }

    /**
     * Test getting a single admin toolbar item
     *
     * @return void
     */
    public function testGetItem()
    {
        // Build up the admin toolbar cache
        $mock = unserialize(file_get_contents(
            __DIR__ . '/../Mocks/admin-toolbar.mock'
        ));

        AAM::api()->cache->set(
            AAM_Framework_Service_AdminToolbar::CACHE_OPTION, $mock
        );

        $server = rest_get_server();
        $result = $server->dispatch($this->prepareRestRequest(
            'GET',
            '/aam/v2/service/admin-toolbar/about',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([
            "slug"          => "about",
            "uri"           => "/wp-admin/about.php",
            "name"          => "About WordPress",
            "is_restricted" => false,
            "parent_id"     => "wp-logo"
        ], $result->get_data());
    }

}