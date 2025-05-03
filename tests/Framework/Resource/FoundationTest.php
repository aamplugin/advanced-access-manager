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

    /**
     * Undocumented function
     *
     * @return void
     */
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

    /**
     * Test current user (both visitor and authenticated) can inherit access controls
     * from its first parent access level
     *
     * @return void
     */
    public function testInheritanceChain()
    {
        $user_a = $this->createUser();

        // Set access controls to role Subscriber
        $this->assertTrue(AAM::api()->widgets('role:subscriber')->deny('dummy_widget'));
        $this->assertTrue(AAM::api()->widgets('role:subscriber')->is_denied('dummy_widget'));
        $this->assertTrue(AAM::api()->widgets("user:{$user_a}")->is_denied('dummy_widget'));

        // Authenticate user
        wp_set_current_user($user_a);

        $this->assertTrue(AAM::api()->widgets()->is_denied('dummy_widget'));

        // Set access controls to default level
        $this->assertTrue(AAM::api()->widgets('default')->deny('another_widget'));
        $this->assertTrue(AAM::api()->widgets('default')->is_denied('another_widget'));
        $this->assertTrue(AAM::api()->widgets('visitor')->is_denied('another_widget'));

        // Logout current user
        wp_set_current_user(0);

        $this->assertTrue(AAM::api()->widgets()->is_denied('another_widget'));
    }

    /**
     * Test current user can inherit access controls from their's first parent
     * access levels
     *
     * @return void
     */
    public function testInheritanceFullChain()
    {
        $user_a = $this->createUser();

        // Set access controls to default level
        $this->assertTrue(AAM::api()->widgets('default')->deny('another_widget'));

        // Authenticate user
        wp_set_current_user($user_a);

        $this->assertTrue(AAM::api()->widgets()->is_denied('another_widget'));
    }

}