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
    AAM_Framework_Utility_Cache,
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

        AAM_Framework_Utility_Cache::set(
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
        $this->assertTrue($service->restrict('wp-logo'));

        // Assert that the entire branch is restricted
        $this->assertTrue($service->is_restricted('wp-logo'));

        // Assert that child item is also restricted
        $this->assertTrue($service->is_restricted('wporg'));
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
        $this->assertTrue($service->restrict('aam'));

        // Assert that the entire branch is restricted
        $this->assertTrue($service->is_restricted('aam'));
        $this->assertFalse($service->is_allowed('aam'));

        // Assert that child item is also restricted
        $this->assertFalse($service->is_restricted('site-name'));
        $this->assertTrue($service->is_allowed('site-name'));
    }

}