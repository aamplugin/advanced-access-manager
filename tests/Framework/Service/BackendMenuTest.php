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
    AAM_Framework_Manager,
    AAM_Service_BackendMenu,
    AAM_Framework_Utility_Cache,
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
        $service = AAM_Framework_Manager::backend_menu();
        $menu    = $service->get_menu();

        // The $menu variable should not be empty
        $this->assertNotEmpty($menu);

        // Double-checking the cache value itself
        $cache = AAM_Framework_Utility_Cache::get(
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

        // Find menu item by the slug
        $service = AAM_Framework_Manager::backend_menu();
        $item    = $service->get_menu_item('aam');

        // The $menu variable should not be empty
        $this->assertNotEmpty($item);

        // Also verifying that we retrieved correct menu item
        $this->assertEquals('aam', $item['slug']);
    }

    /**
     * Test that admin menu item permissions can be updated successfully
     *
     * @return void
     */
    public function testUpdateAdminMenuItemPermission()
    {
        $service = AAM_Framework_Manager::backend_menu([
            'access_level' => AAM::api()->role('editor')
        ]);

        // Update permission for a single submenu item
        $result = $service->update_menu_item_permission(
            'edit-tags.php?taxonomy=category', 'deny'
        );

        // Assert the the result of the execution is an array the represents a menu
        // item
        $this->assertEquals('edit-tags.php?taxonomy=category', $result['slug']);
        $this->assertTrue($result['is_restricted']);

        // Update the entire menu branch and ensure that all sub items are restricted
        $service->update_menu_item_permission('upload.php', 'deny', true);

        $this->assertTrue($service->is_restricted('upload.php', true));
        $this->assertTrue($service->is_restricted('upload.php'));
        $this->assertTrue($service->is_restricted('media-new.php'));
    }

    /**
     * Testing that restricted admin menu items are actually removed
     *
     * The restricted menu items should be removed from both $menu and $submenu
     * global variables
     *
     * @return void
     */
    public function testAdminMenuFiltering()
    {
        global $menu, $submenu;

        $user_id = $this->createUser([ 'role' => 'administrator' ]);
        $service = AAM_Framework_Manager::backend_menu([
            'access_level' => AAM::api()->role('administrator')
        ]);

        // Update permission for a single submenu item
        $service->update_menu_item_permission(
            'edit-tags.php?taxonomy=category', 'deny'
        );

        // Update the entire menu branch and ensure that all sub items are restricted
        $service->update_menu_item_permission('upload.php', 'deny', true);

        // Mocking backend menu
        $this->_mockAdminMenu();

        // Setting current user that is an admin
        wp_set_current_user($user_id);

        // Trigger the admin menu filtering process
        AAM_Service_BackendMenu::getInstance()->filter_menu();

        // Asserting that submenu does not contain restricted branch
        $this->assertNotContains('upload.php', array_keys($submenu));
        $this->assertEmpty(array_filter($menu, function($m) {
            return $m[2] === 'upload.php';
        }));

        // Asserting that a single submenu item is restricted
        $this->assertEmpty(array_filter($submenu['edit.php'], function($m) {
            return $m[2] === 'edit-tags.php?taxonomy=category';
        }));
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
        $mock = unserialize(file_get_contents(__DIR__ . '/admin-menu.mock'));

        $menu    = $mock['menu'];
        $submenu = $mock['submenu'];
    }

}