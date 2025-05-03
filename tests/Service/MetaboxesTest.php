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
    AAM\UnitTest\Utility\TestCase;

/**
 * AAM Metaboxes service test suite
 */
final class MetaboxesTest extends TestCase
{

    /**
     * Test the fi
     *
     * @return void
     */
    public function testMetaboxFiltering()
    {
        global $wp_meta_boxes;

        $this->_mockMetaboxes();

        $user_a = $this->createUser([ 'role' => 'editor' ]);

        // Setting current user
        wp_set_current_user($user_a);

        // Verify that to-be restricted metaboxes still listed
        do_action('in_admin_header');

        $this->assertNotEmpty($wp_meta_boxes['page']['side']['core']['pageparentdiv']);
        $this->assertNotEmpty($wp_meta_boxes['page']['side']['low']['postimagediv']);
        $this->assertNotEmpty($wp_meta_boxes['page']['normal']['core']['postcustom']);

        // Setting permissions for the Editor role
        $service = AAM::api()->metaboxes('role:editor');
        $service->deny('post_thumbnail_meta_box', 'page');
        $service->deny('page_attributes_meta_box', 'page');
        $service->deny('post_custom_meta_box', 'page');

        // Verify that restricted metaboxes are not listed
        do_action('in_admin_header');

        $this->assertEmpty($wp_meta_boxes['page']['side']['core']['pageparentdiv']);
        $this->assertEmpty($wp_meta_boxes['page']['side']['low']['postimagediv']);
        $this->assertEmpty($wp_meta_boxes['page']['normal']['core']['postcustom']);
    }

    /**
     * Mocking metaboxes globals
     *
     * @return void
     *
     * @access private
     */
    private function _mockMetaboxes()
    {
        global $post, $wp_meta_boxes;

        $test_post = $this->createPost([ 'post_type' => 'page' ]);

        // Set global post
        $post = get_post($test_post);

        // Read the mock data and populate super globals
        $wp_meta_boxes = unserialize(file_get_contents(
            __DIR__ . '/../Mocks/metaboxes.mock'
        ));
    }

}