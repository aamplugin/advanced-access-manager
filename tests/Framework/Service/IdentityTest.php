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
 * AAM Identity service test suite
 */
final class IdentityTest extends TestCase
{

    /**
     * Testing that we can set various permissions and they can be properly
     * resolved for the resource
     *
     * @return void
     */
    public function testManageResourcePermissions()
    {
        $user_a = $this->createUser([ 'role' => 'subscriber' ]);
        $user_b = $this->createUser([ 'role' => 'subadmin' ]);

        // Settings permissions
        $this->assertTrue(AAM::api()->roles('role:subadmin')->deny('editor', 'edit_user'));
        $this->assertTrue(AAM::api()->users('role:subadmin')->deny($user_a, 'promote_user'));

        // Verifying permissions
        $this->assertTrue(AAM::api()->roles('role:subadmin')->is_denied_to('editor', 'edit_user'));
        $this->assertFalse(AAM::api()->roles('role:subadmin')->is_allowed_to('editor', 'edit_user'));
        $this->assertTrue(AAM::api()->users('role:subadmin')->is_denied_to($user_a, 'promote_user'));
        $this->assertFalse(AAM::api()->users('role:subadmin')->is_allowed_to($user_a, 'promote_user'));

        // Verify that permissions are properly inherited
        $this->assertTrue(AAM::api()->roles('user:' . $user_b)->is_denied_to('editor', 'edit_user'));
        $this->assertFalse(AAM::api()->roles('user:' . $user_b)->is_allowed_to('editor', 'edit_user'));
        $this->assertTrue(AAM::api()->users('user:' . $user_b)->is_denied_to($user_a, 'promote_user'));
        $this->assertFalse(AAM::api()->users('user:' . $user_b)->is_allowed_to($user_a, 'promote_user'));
    }

    /**
     * Test that we can set permissions and properly resolve them for user & role
     * identities
     *
     * @return void
     */
    public function testManageIdentityPermissions()
    {
        $user_a = $this->createUser([ 'role' => 'subscriber' ]);
        $user_b = $this->createUser([ 'role' => 'subadmin' ]);

        // Settings permissions
        $this->assertTrue(
            AAM::api()->roles('role:subadmin')->deny('editor', 'edit_user')
        );
        $this->assertTrue(
            AAM::api()->users('role:subadmin')->deny($user_a, 'promote_user')
        );

        // Confirm that identities are properly protected
        $this->assertTrue(AAM::api()->roles('role:subadmin')->is_denied_to(
            'editor', 'edit_user'
        ));
        $this->assertFalse(AAM::api()->roles('role:subadmin')->is_allowed_to(
            'editor', 'edit_user'
        ));
        $this->assertTrue(AAM::api()->users('role:subadmin')->is_denied_to(
            $user_a, 'promote_user'
        ));
        $this->assertFalse(AAM::api()->users('role:subadmin')->is_allowed_to(
            $user_a, 'promote_user'
        ));

        // Confirm that identities are properly protected
        $this->assertTrue(AAM::api()->roles('user:' . $user_b)->is_denied_to(
            'editor', 'edit_user'
        ));
        $this->assertFalse(AAM::api()->roles('user:' . $user_b)->is_allowed_to(
            'editor', 'edit_user'
        ));
        $this->assertTrue(AAM::api()->users('user:' . $user_b)->is_denied_to(
            $user_a, 'promote_user'
        ));
        $this->assertFalse(AAM::api()->users('user:' . $user_b)->is_allowed_to(
            $user_a, 'promote_user'
        ));
    }

    /**
     * Testing aggregation for roles
     *
     * @return void
     */
    public function testRoleAggregation()
    {
        $this->assertTrue(AAM::api()->roles()->deny('author', 'list_role'));
        $this->assertTrue(AAM::api()->roles()->deny('subscriber', 'list_role'));

        // Making sure that the aggregator is working as expected
        $this->assertEquals([
            'author' => [
                'list_role' => [
                    'effect' => 'deny'
                ]
            ],
            'subscriber' => [
                'list_role' => [
                    'effect' => 'deny'
                ]
            ]
        ], AAM::api()->roles()->aggregate());
    }

    /**
     * Making sure that aggregate take access policies Role resource into
     * consideration
     *
     * @return void
     */
    public function testRoleAggregationWithPolicy()
    {
        // Create a policy that hides 2 posts
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Role:subscriber",
                    "Action": "List"
                },
                {
                    "Effect": "deny",
                    "Resource": "Role:author:users",
                    "Action": "List"
                }
            ]
        }'));

        // Making sure that the aggregator is working as expected
        $this->assertEquals([
            'subscriber' => [
                'list_role' => [
                    'effect' => 'deny'
                ]
            ],
            'author' => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ]
        ], AAM::api()->roles()->aggregate());
    }

    /**
     * Test user aggregation
     *
     * @return void
     */
    public function testUserAggregation()
    {
        $user_a = $this->createUser();
        $user_b = $this->createUser();

        $this->assertTrue(AAM::api()->users()->deny($user_a, 'list_user'));
        $this->assertTrue(AAM::api()->users()->deny($user_b, 'list_user'));

        // Making sure that the aggregator is working as expected
        $this->assertEquals([
            $user_a => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ],
            $user_b => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ]
        ], AAM::api()->users()->aggregate());
    }

    /**
     * Test user aggregation with access policies
     *
     * @return void
     */
    public function testUserAggregationWithPolicy()
    {
        $user_a = $this->createUser();
        $user_b = $this->createUser([ 'user_login' => 'test_user_b' ]);
        $user_c = $this->createUser([ 'user_email' => 'test@aamportal.com' ]);

        // Create a policy that hides 2 posts
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "User:' . $user_a . '",
                    "Action": "List"
                },
                {
                    "Effect": "deny",
                    "Resource": "User:test_user_b",
                    "Action": "List"
                },
                {
                    "Effect": "deny",
                    "Resource": "User:test@aamportal.com",
                    "Action": "List"
                }
            ]
        }'));

        // Making sure that the aggregator is working as expected
        $this->assertEquals([
            $user_a => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ],
            $user_b => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ],
            $user_c => [
                'list_user' => [
                    'effect' => 'deny'
                ]
            ]
        ], AAM::api()->users()->aggregate());
    }

}