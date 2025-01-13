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
    AAM_Framework_Type_Resource,
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_Settings;

/**
 * Test class for AAM framework resource foundational functionality
 */
final class FoundationTest extends TestCase
{

    public function testSetPermissions()
    {
        $post     = get_post($this->createPost());
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::POST
        );

        // Making sure we can add permissions to the resource
        $this->assertTrue($resource->set_permissions([
            'read' => [
                'effect' => 'deny'
            ]
        ], $post));

        // Read raw permissions
        $raw = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'visitor' => [
                'post' => [
                    "{$post->ID}|{$post->post_type}" => [
                        'read' => [
                            'effect' => 'deny'
                        ]
                    ]
                ]
            ]
        ], $raw);
    }

    public function testInheritanceChain()
    {
        // Set access on the default level for a dummy metabox
        $this->assertTrue(AAM::api()->metaboxes('default')->deny('dummy_metabox'));
    }

}