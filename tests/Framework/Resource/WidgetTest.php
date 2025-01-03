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
 * Test class for the AAM "Widget" framework resource
 */
final class WidgetTest extends TestCase
{

    /**
     * Testing that we can properly initialize the widget resource
     *
     * @return void
     */
    public function testWidgetResourceInit()
    {
        // Verifying that widget is allowed
        $this->assertFalse(AAM::api()->widgets()->is_restricted('wp_dashboard_site_health'));
        $this->assertFalse(AAM::api()->widgets()->is_restricted('wp_widget_search'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Resource": [
                    "Widget:wp_dashboard_site_health",
                    "Widget:wp_widget_search"
                ],
                "Effect": "deny"
            }
        }'));

        // Verifying that widgets are restricted
        $this->assertTrue(AAM::api()->widgets()->is_restricted('wp_dashboard_site_health'));
        $this->assertTrue(AAM::api()->widgets()->is_restricted('wp_widget_search'));
    }

}