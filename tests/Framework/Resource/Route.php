<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "API Route" framework resource
 */
final class RouteTest extends TestCase
{

    /**
     * Testing that we can properly initialize the RESTful API route resource
     *
     * @return void
     */
    public function testRouteResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(AAM::api()->api_routes()->is_denied('/oembed/1.0/proxy'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Route:/oembed/1.0/proxy:get"
                }
            ]
        }'));

        // Verifying that API route is restricted
        $this->assertTrue(AAM::api()->api_routes()->is_denied('/oembed/1.0/proxy'));
    }

}