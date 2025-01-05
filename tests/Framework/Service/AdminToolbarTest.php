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
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_AdminToolbar;

/**
 * Test class for the AAM "Admin Toolbar" framework service
 */
final class AdminToolbarTest extends TestCase
{

    /**
     * Prepare the admin toolbar cache
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        // Build up the admin toolbar cache
        $mock = unserialize(file_get_contents(
            __DIR__ . '/../../Mocks/admin-toolbar.mock'
        ));

        AAM::api()->cache->set(
            AAM_Framework_Service_AdminToolbar::CACHE_DB_OPTION, $mock
        );
    }

    /**
     * Test that toolbar item permissions can be updated successfully
     *
     * @return void
     */
    public function testWholeBranchPermission()
    {
        $service = AAM::api()->admin_toolbar('role:editor');

        // Update permission for the entire branch
        $this->assertTrue($service->deny('wp-logo'));

        // Assert that the entire branch is restricted
        $this->assertTrue($service->is_denied('wp-logo'));

        // Assert that child item is also restricted
        $this->assertTrue($service->is_denied('wporg'));
    }

    /**
     * Test that toolbar item permissions can be updated successfully for one
     * subitem ONLY
     *
     * @return void
     */
    public function testSubitemPermission()
    {
        $service = AAM::api()->admin_toolbar('role:editor');

        // Update permission for the entire branch
        $this->assertTrue($service->deny('aam'));

        // Assert that the entire branch is restricted
        $this->assertTrue($service->is_denied('aam'));
        $this->assertFalse($service->is_allowed('aam'));

        // Assert that child item is also restricted
        $this->assertFalse($service->is_denied('site-name'));
        $this->assertTrue($service->is_allowed('site-name'));
    }

}