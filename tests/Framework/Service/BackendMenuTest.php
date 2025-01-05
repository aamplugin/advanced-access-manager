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
    AAM_Framework_Service_BackendMenu;

/**
 * Test class for the AAM "Backend Menu" framework service
 */
final class BackendMenuTest extends TestCase
{

    /**
     * Testing that the backend menu cache is properly initializes
     *
     * This test case will attempt to initialize the backend menu cache and then
     * validate that it is properly returned by the service
     *
     * @return void
     */
    public function testBackendMenuCacheInitialization() : void
    {
        // Mocking backend menu
        $this->_mockAdminMenu();

        // Triggering the get menu method to see if it'll pick up the mock data
        $service = AAM::api()->backend_menu();
        $menu    = $service->get_items();

        // The $menu variable should not be empty
        $this->assertNotEmpty($menu);

        // Double-checking the cache value itself
        $cache = AAM::api()->cache->get(
            AAM_Framework_Service_BackendMenu::CACHE_DB_OPTION
        );

        // The $cache should not be empty
        $this->assertNotEmpty($cache);
    }

    /**
     * Testing retrieval of a single admin menu item
     *
     * The test assumes that there is the "AAM" admin menu item registered and it can
     * be retrieved with the service.
     *
     * @return void
     */
    public function testGetAdminMenuItem() : void
    {
        // Mocking backend menu
        $this->_mockAdminMenu();

        $service = AAM::api()->backend_menu();

        // Find top backend menu item by the slug
        $item = $service->get_item('menu/aam');

        // The $menu variable should not be empty
        $this->assertNotEmpty($item);

        // Also verifying that we retrieved correct menu item
        $this->assertEquals('menu/aam', $item['slug']);
    }

    /**
     * Test that admin menu item permissions can be updated successfully
     *
     * @return void
     */
    public function testUpdateAdminMenuItemPermission()
    {
        $service = AAM::api()->backend_menu(
            AAM::api()->role('editor')
        );

        // Update permission for a single submenu item
        $this->assertTrue($service->deny('edit-tags.php?taxonomy=category'));

        // Assert that submenu item is actually restricted
        $this->assertTrue($service->is_denied('edit-tags.php?taxonomy=category'));

        // Update the entire menu branch and ensure that all sub items are restricted
        $service->deny('menu/upload.php');

        $this->assertTrue($service->is_denied('menu/upload.php'));
        $this->assertTrue($service->is_denied('upload.php'));
        $this->assertTrue($service->is_denied('media-new.php'));
    }

    /**
     * Mocking admin menu globals
     *
     * @return void
     *
     * @access private
     */
    private function _mockAdminMenu()
    {
        global $menu, $submenu;

        // Read the mock data and populate super globals
        $mock = unserialize(file_get_contents(
            __DIR__ . '/../../Mocks/admin-menu.mock'
        ));

        $menu    = $mock['menu'];
        $submenu = $mock['submenu'];
    }

}