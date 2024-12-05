<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "API Routes" framework service
 */
final class ApiRoutesTest extends TestCase
{

    /**
     * Test permissions can be set properly
     *
     * @return void
     */
    public function testSetRestriction()
    {
        $service = AAM::api()->api_routes();
        $request = new \WP_REST_Request('GET', '/oembed/1.0');

        // Set restrictions
        $this->assertTrue($service->restrict($request));
        $this->assertTrue($service->restrict('/oembed/1.0/embed', 'GET'));

        // Assert that both endpoints are restricted
        $this->assertTrue($service->is_restricted('/oembed/1.0'));
        $this->assertFalse($service->is_allowed('/oembed/1.0'));
        $this->assertTrue($service->is_restricted($request));
        $this->assertFalse($service->is_allowed($request));
        $this->assertTrue($service->is_restricted('/oembed/1.0/embed'));
        $this->assertFalse($service->is_allowed('/oembed/1.0/embed'));

        // Making sure that HTTP method is taken into consideration
        $this->assertTrue($service->restrict('/aam/v2/service/jwts'));
        $this->assertTrue($service->is_restricted('/aam/v2/service/jwts'));
        $this->assertFalse($service->is_restricted('/aam/v2/service/jwts', 'POST'));
        $this->assertFalse($service->is_restricted('/aam/v2/service/jwts', 'DELETE'));
    }

}