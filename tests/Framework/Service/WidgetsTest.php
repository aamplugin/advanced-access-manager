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
     * Test settings permissions to widgets with slug
     *
     * @return void
     */
    public function testSettingPermissionsWithSlug()
    {
        $service = AAM::api()->widgets('role:author');

        // Set permission
        $this->assertTrue($service->deny('wp_dashboard_site_health'));

        // Assert that the widget is restricted
        $this->assertTrue($service->is_denied('wp_dashboard_site_health'));
        $this->assertFalse($service->is_allowed('wp_dashboard_site_health'));

        // Set permission
        $this->assertTrue($service->allow('wp_dashboard_right_now'));

        // Assert that the widget is restricted
        $this->assertFalse($service->is_denied('wp_dashboard_right_now'));
        $this->assertTrue($service->is_allowed('wp_dashboard_right_now'));
    }

    /**
     * Test settings permissions to widgets with array
     *
     * @return void
     */
    public function testSettingPermissionsWithArray()
    {
        $service = AAM::api()->widgets('role:editor');

        // Defining test widgets
        $wp_dashboard_site_health = [
            'id'       => 'dashboard_site_health',
            'title'    => 'Site Health Status',
            'callback' => 'wp_dashboard_site_health'
        ];
        $wp_dashboard_right_now = [
            'id'       => 'dashboard_right_now',
            'title'    => 'At a Glance',
            'callback' => 'wp_dashboard_right_now',
        ];

        // Set permission
        $this->assertTrue($service->deny($wp_dashboard_site_health));

        // Assert that the widget is restricted
        $this->assertTrue($service->is_denied($wp_dashboard_site_health));
        $this->assertFalse($service->is_allowed($wp_dashboard_site_health));

        // Set permission
        $this->assertTrue($service->allow($wp_dashboard_right_now));

        // Assert that the widget is restricted
        $this->assertFalse($service->is_denied($wp_dashboard_right_now));
        $this->assertTrue($service->is_allowed($wp_dashboard_right_now));
    }

}