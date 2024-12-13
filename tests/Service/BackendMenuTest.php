<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    AAM_Service_BackendMenu,
    AAM\UnitTest\Utility\TestCase;

/**
 * AAM Backend Menu service test suite
 */
final class BackendMenuTest extends TestCase
{

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

        $user_id = $this->createUser([ 'role' => 'editor' ]);
        $service = AAM::api()->backend_menu([
            'access_level' => AAM::api()->role('editor')
        ]);

        // Update permission for a single submenu item
        $service->restrict('edit-tags.php?taxonomy=category');

        // Update the entire menu branch and ensure that all sub items are restricted
        $service->restrict('menu/upload.php');

        // Mocking backend menu
        $this->_mockAdminMenu();

        // Setting current user that is an admin
        wp_set_current_user($user_id);

        // Trigger the admin menu filtering process
        AAM_Service_BackendMenu::get_instance()->filter_menu();

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
        $mock = unserialize(file_get_contents(
            __DIR__ . '/../Mocks/admin-menu.mock'
        ));

        $menu    = $mock['menu'];
        $submenu = $mock['submenu'];
    }

}