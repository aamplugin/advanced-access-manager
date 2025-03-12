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
 * Test class for the AAM "Metaboxes" framework service
 */
final class MetaboxesTest extends TestCase
{

    /**
     * Test managing metabox permissions by slug
     *
     * @return void
     */
    public function testSettingPermissionsWithSlug()
    {
        $service = AAM::api()->metaboxes('role:author');

        // Set permission for a single metabox
        $this->assertTrue($service->deny('post_submit_meta_box'));

        // Assert metabox permissions
        $this->assertTrue($service->is_denied('post_submit_meta_box'));

        // Set permission
        $this->assertTrue($service->deny('post_thumbnail_meta_box', 'page'));

        // Assert metabox permissions
        $this->assertTrue($service->is_denied('post_thumbnail_meta_box', 'page'));
        $this->assertTrue($service->is_denied('post_thumbnail_meta_box', 'page'));
        $this->assertTrue($service->is_allowed('post_thumbnail_meta_box', 'post'));

        // Set allow permission
        $this->assertTrue($service->allow('post_custom_meta_box', 'page'));

        // Assert metabox permissions
        $this->assertTrue($service->is_allowed('post_custom_meta_box', 'page'));
    }

     /**
     * Test managing metabox permissions with metabox array
     *
     * @return void
     */
    public function testSettingPermissionsWithArray()
    {
        $service = AAM::api()->metaboxes('role:editor');

        // Defining test metaboxes
        $post_submit_meta_box = [
            'id'       => 'submitdiv',
            'title'    => 'Publish',
            'callback' => 'post_submit_meta_box'
        ];
        $post_thumbnail_meta_box = [
            'id'       => 'postimagediv',
            'title'    => 'Featured image',
            'callback' => 'post_thumbnail_meta_box'
        ];
        $post_custom_meta_box = [
            'id'       => 'postcustom',
            'title'    => 'Custom Fields',
            'callback' => 'post_custom_meta_box'
        ];

        // Set permission for a single metabox
        $this->assertTrue($service->deny($post_submit_meta_box));

        // Assert metabox permissions
        $this->assertTrue($service->is_denied($post_submit_meta_box));

        // Set permission
        $this->assertTrue($service->deny($post_thumbnail_meta_box, 'page'));

        // Assert metabox permissions
        $this->assertTrue($service->is_denied($post_thumbnail_meta_box, 'page'));
        $this->assertTrue($service->is_denied('post_thumbnail_meta_box', 'page'));
        $this->assertTrue($service->is_allowed($post_thumbnail_meta_box, 'post'));

        // Set allow permission
        $this->assertTrue($service->allow($post_custom_meta_box, 'page'));

        // Assert metabox permissions
        $this->assertTrue($service->is_allowed($post_custom_meta_box, 'page'));
    }

}