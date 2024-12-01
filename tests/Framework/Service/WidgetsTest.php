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
 * Test class for the AAM "Widgets" framework service
 */
final class WidgetsTest extends TestCase
{

    /**
     * Test we can set permissions for metaboxes
     *
     * @return void
     */
    public function testSettingPermissions()
    {
        $service = AAM::api()->widgets('role:author');

        // Set permission
        $this->assertTrue($service->restrict('dashboard_dashboard_right_now'));

        // Assert that the widget is restricted
        $this->assertTrue($service->is_restricted('dashboard_dashboard_right_now'));
        $this->assertFalse($service->is_allowed('dashboard_dashboard_right_now'));

        // Set permission
        $this->assertTrue($service->allow('dashboard_dashboard_activity'));

        // Assert that the widget is restricted
        $this->assertFalse($service->is_restricted('dashboard_dashboard_activity'));
        $this->assertTrue($service->is_allowed('dashboard_dashboard_activity'));

    }

}