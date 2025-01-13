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
        $this->assertTrue($service->deny($request));
        $this->assertTrue($service->deny('GET /oembed/1.0/embed'));

        // Assert that both endpoints are restricted
        $this->assertTrue($service->is_denied('/oembed/1.0'));
        $this->assertFalse($service->is_allowed('/oembed/1.0'));
        $this->assertTrue($service->is_denied($request));
        $this->assertFalse($service->is_allowed($request));
        $this->assertTrue($service->is_denied('/oembed/1.0/embed'));
        $this->assertFalse($service->is_allowed('/oembed/1.0/embed'));

        // Making sure that HTTP method is taken into consideration
        $this->assertTrue($service->deny('/aam/v2/service/jwts'));
        $this->assertTrue($service->is_denied('/aam/v2/service/jwts'));
        $this->assertFalse($service->is_denied('POST /aam/v2/service/jwts'));
        $this->assertFalse($service->is_denied('DELETE /aam/v2/service/jwts'));
    }

}