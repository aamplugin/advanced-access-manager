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
    AAM\UnitTest\Utility\TestCase;

/**
 * AAM API Routes service test suite
 */
final class ApiRoutesTest extends TestCase
{

    /**
     * Assert that we can restrict RESTful API endpoints
     *
     * @return void
     */
    public function testRouteRestriction()
    {
        $service = AAM::api()->api_routes();
        $request = new \WP_REST_Request('GET', '/oembed/1.0');

        // Confirm that we can get a positive response
        $this->assertTrue(apply_filters('rest_pre_dispatch', true, '__', $request));

        // Restrict endpoint and assert that we are getting the WP_Error response
        $this->assertTrue($service->deny($request));
        $this->assertEquals(
            \WP_Error::class,
            get_class(apply_filters('rest_pre_dispatch', true, '__', $request))
        );
    }

}