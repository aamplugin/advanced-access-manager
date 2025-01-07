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
 * AAM Identity service test suite
 */
final class IdentityTest extends TestCase
{

    /**
     * Making sure that roles are properly filtered
     *
     * @return void
     */
    public function testRoleVisibility()
    {
        $user_a = $this->createUser([ 'role' => 'editor' ]);

        // Setting new current user
        wp_set_current_user($user_a);

        // Making sure that we can see about to be hidden role
        $this->assertArrayHasKey('subscriber', get_editable_roles());

        // Defining permissions and hiding role
        $service = AAM::api()->identities();

        $this->assertTrue($service->role('subscriber')->deny('list_role'));

        // Making sure that we can see about to be hidden role
        $this->assertArrayNotHasKey('subscriber', get_editable_roles());
    }

    /**
     * Test users visibility
     *
     * This test covers many different ways to query users and ensuring that all these
     * cases are properly handled
     *
     * @return void
     */
    public function testUserVisibility()
    {
        $user_a = $this->createUser([ 'role' => 'editor' ]);
        $user_b = $this->createUser([ 'role' => 'subscriber' ]);
        $user_c = $this->createUser([ 'role' => 'contributor' ]);

        // Setting new current user
        wp_set_current_user($user_a);

        $service = AAM::api()->identities();

        // Ensure that we can see all the users
        $users = array_map('intval', get_users([
            'fields' => 'ids',
            'number' => 10
        ]));

        $this->assertContains($user_a, $users);
        $this->assertContains($user_b, $users);
        $this->assertContains($user_c, $users);

        // Case #1. Hide an individual user
        $this->assertTrue($service->user($user_b)->deny('list_user'));

        $users = array_map('intval', get_users([
            'fields' => 'ids',
            'number' => 10
        ]));

        $this->assertContains($user_a, $users);
        $this->assertNotContains($user_b, $users);
        $this->assertContains($user_c, $users);

        // Reset permissions
        $this->assertTrue($service->reset());

        // Case #2. Hide users through the role
        $this->assertTrue($service->role('contributor')->deny('list_users'));

        $users = array_map('intval', get_users([
            'fields' => 'ids',
            'number' => 10
        ]));

        $this->assertContains($user_a, $users);
        $this->assertContains($user_b, $users);
        $this->assertNotContains($user_c, $users);

        // Reset permissions
        $this->assertTrue($service->reset());

        // Case #3. Manage querying with "include" query param
        $this->assertTrue($service->user($user_b)->deny('list_user'));

        $result_a = array_map('intval', get_users([
            'fields'  => 'ids',
            'include' => [ $user_b ],
            'number'  => 10
        ]));

        $result_b = array_map('intval', get_users([
            'fields'  => 'ids',
            'include' => [ $user_b, $user_a ],
            'number'  => 10
        ]));

        $this->assertEmpty($result_a);
        $this->assertContains($user_a, $result_b);
        $this->assertNotContains($user_b, $result_b);

        // Reset permissions
        $this->assertTrue($service->reset());

        // Case #4. Manage querying with "exclude" query param
        $this->assertTrue($service->user($user_b)->deny('list_user'));

        $result_a = array_map('intval', get_users([
            'fields'  => 'ids',
            'exclude' => [ $user_a ],
            'number'  => 10
        ]));

        $result_b = array_map('intval', get_users([
            'fields'  => 'ids',
            'exclude' => [ $user_b, $user_a ],
            'number'  => 10
        ]));

        $this->assertContains($user_c, $result_a);
        $this->assertNotContains($user_a, $result_a);
        $this->assertNotContains($user_b, $result_a);
        $this->assertNotContains($user_a, $result_b);
        $this->assertNotContains($user_b, $result_b);
        $this->assertContains($user_c, $result_b);

        // Reset permissions
        $this->assertTrue($service->reset());

        // Case #5. Manage querying with "role__in" query param
        $this->assertTrue($service->role('contributor')->deny('list_users'));

        $result_a = array_map('intval', get_users([
            'fields'   => 'ids',
            'role__in' => [ 'contributor' ],
            'number'   => 10
        ]));

        $result_b = array_map('intval', get_users([
            'fields'   => 'ids',
            'role__in' => [ 'subscriber', 'contributor' ],
            'number'   => 10
        ]));

        $this->assertEmpty($result_a);
        $this->assertContains($user_b, $result_b);
        $this->assertNotContains($user_c, $result_b);
        $this->assertNotContains($user_a, $result_b);

        // Reset permissions
        $this->assertTrue($service->reset());

        // Case #6. Manage querying with "role__not_in" query param
        $this->assertTrue($service->role('contributor')->deny('list_users'));

        $result_a = array_map('intval', get_users([
            'fields'       => 'ids',
            'role__not_in' => [ 'contributor' ],
            'number'       => 10
        ]));

        $result_b = array_map('intval', get_users([
            'fields'       => 'ids',
            'role__not_in' => [ 'subscriber' ],
            'number'       => 10
        ]));

        $this->assertNotContains($user_c, $result_a);
        $this->assertContains($user_a, $result_a);
        $this->assertContains($user_b, $result_a);
        $this->assertContains($user_a, $result_b);
        $this->assertNotContains($user_b, $result_b);
        $this->assertNotContains($user_c, $result_b);

        // Reset permissions
        $this->assertTrue($service->reset());
    }

    /**
     * Test multi-role setup for user visibility
     *
     * @return void
     */
    public function testMultiRoleUserVisibility()
    {
        // Enabling multi-role support
        $this->assertTrue(AAM::api()->config->set(
            'core.settings.multi_access_levels', true
        ));

        $user_a = $this->createUser([ 'role' => 'editor' ]);
        $user_b = $this->createUser([ 'role' => [ 'subscriber', 'contributor' ] ]);

        // Setting current user
        wp_set_current_user($user_a);

        // Setting permissions and hiding users in one role
        $this->assertTrue(
            AAM::api()->identities('role:editor')->role('contributor')->deny('list_users')
        );

        $users = array_map('intval', get_users([
            'fields' => 'ids',
            'number' => 10
        ]));

        $this->assertContains($user_a, $users);
        $this->assertNotContains($user_b, $users);
    }

    /**
     * Test that we can control fundamental user permissions like edit, delete or
     * promote
     *
     * @return void
     */
    public function testUserBasicPermissions()
    {
        $user_a = $this->createUser([ 'role' => 'subadmin' ]);
        $user_b = $this->createUser([ 'role' => 'subscriber' ]);
        $user_c = $this->createUser([ 'role' => 'contributor' ]);

        // Set current user
        wp_set_current_user($user_a);

        // Verify that current user has the ability to perform actions on users
        $this->assertTrue(current_user_can('edit_user', $user_b));
        $this->assertTrue(current_user_can('edit_user', $user_c));
        $this->assertTrue(current_user_can('delete_user', $user_b));
        $this->assertTrue(current_user_can('delete_user', $user_c));
        $this->assertTrue(current_user_can('promote_user', $user_b));
        $this->assertTrue(current_user_can('promote_user', $user_c));

        // Set permissions on the role level
        $this->assertTrue(AAM::api()->identities('role:subadmin')
                                    ->role('subscriber')
                                    ->deny(['edit_users', 'delete_users', 'promote_users'])
        );

        // Verifying permission
        $this->assertFalse(current_user_can('edit_user', $user_b));
        $this->assertFalse(current_user_can('delete_user', $user_b));
        $this->assertFalse(current_user_can('promote_user', $user_b));
        $this->assertTrue(current_user_can('edit_user', $user_c));
        $this->assertTrue(current_user_can('delete_user', $user_c));
        $this->assertTrue(current_user_can('promote_user', $user_c));

        // Set permission for an individual user
        $service = AAM::api()->identities('role:subadmin')->user($user_c);

        $this->assertTrue($service->deny('edit_user'));
        $this->assertTrue($service->deny('delete_user'));
        $this->assertTrue($service->deny('promote_user'));

        $this->assertFalse(current_user_can('edit_user', $user_c));
        $this->assertFalse(current_user_can('delete_user', $user_c));
        $this->assertFalse(current_user_can('promote_user', $user_c));
    }

    /**
     * Testing that we can properly manage password change controls and filter out
     * incoming data to remove password if access restricted to change password.
     *
     * @return void
     */
    public function testPasswordControlsPermissions()
    {
        $user_a = $this->createUser([ 'role' => 'subadmin' ]);
        $user_b = $this->createUser([ 'role' => 'subscriber' ]);
        $user_c = $this->createUser([ 'role' => 'contributor' ]);

        // Set current user
        wp_set_current_user($user_a);

        // Set permissions on the role level first
        $this->assertTrue(AAM::api()->identities('role:subadmin')
                                    ->role('subscriber')
                                    ->deny('change_users_password')
        );

        $pass1 = $pass2 = uniqid('testpass_');
        $target_user = get_user($user_b);

        // Verify that password controls are properly handled
        $this->assertFalse(apply_filters('show_password_fields', true, $target_user));
        $this->assertFalse(apply_filters('allow_password_reset', true, $user_b));

        // Verify that passwords are cleared
        do_action_ref_array(
            'check_passwords',
            [ $target_user->user_login, &$pass1, &$pass2 ]
        );
        $this->assertNull($pass1);
        $this->assertNull($pass2);

        // Verify that REST is handled correctly
        $data = (object) [
            'user_login' => $target_user->user_login,
            'user_pass'  => uniqid()
        ];

        $data = apply_filters('rest_pre_insert_user', $data, [ 'id' => $user_b ]);

        $this->assertObjectNotHasProperty('user_pass', $data);

        // Set permissions on the user level
        $this->assertTrue(AAM::api()->identities('role:subadmin')
                                    ->user($user_c)
                                    ->deny('change_user_password')
        );

        $pass1 = $pass2 = uniqid('testpass_');
        $target_user = get_user($user_c);

        // Verify that password controls are properly handled
        $this->assertFalse(apply_filters('show_password_fields', true, $target_user));
        $this->assertFalse(apply_filters('allow_password_reset', true, $user_c));

        // Verify that passwords are cleared
        do_action_ref_array(
            'check_passwords',
            [ $target_user->user_login, &$pass1, &$pass2 ]
        );
        $this->assertNull($pass1);
        $this->assertNull($pass2);

        // Verify that REST is handled correctly
        $data = (object) [
            'user_login' => $target_user->user_login,
            'user_pass'  => uniqid()
        ];

        $data = apply_filters('rest_pre_insert_user', $data, [ 'id' => $user_c ]);

        $this->assertObjectNotHasProperty('user_pass', $data);
    }

}