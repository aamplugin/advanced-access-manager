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
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $user_b  = $this->createUser([ 'role' => 'administrator' ]);
        $service = AAM::api()->identities('role:administrator');

        $role_identity = $service->role('editor');
        $user_identity = $service->user($user_a);

        // Settings permissions
        $this->assertTrue($role_identity->deny('edit_users'));
        $this->assertTrue($user_identity->deny('promote_user'));

        // Verifying permissions
        $this->assertTrue($service->role('editor')->is_denied_to('edit_users'));
        $this->assertFalse($service->role('editor')->is_allowed_to('edit_users'));
        $this->assertTrue($service->user($user_a)->is_denied_to('promote_user'));
        $this->assertFalse($service->user($user_a)->is_allowed_to('promote_user'));

        // Verify that permissions are properly inherited
        $service = AAM::api()->identities('user:' . $user_b);

        $this->assertTrue($service->role('editor')->is_denied_to('edit_users'));
        $this->assertFalse($service->role('editor')->is_allowed_to('edit_users'));
        $this->assertTrue($service->user($user_a)->is_denied_to('promote_user'));
        $this->assertFalse($service->user($user_a)->is_allowed_to('promote_user'));
    }

    /**
     * Test that we can set permissions and properly resolve them for user & role
     * identities
     *
     * @return void
     */
    public function testManageIdentityPermissions()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $user_b  = $this->createUser([ 'role' => 'administrator' ]);
        $service = AAM::api()->identities('role:administrator');

        $role_identity = $service->role('editor');
        $user_identity = $service->user($user_a);

        // Settings permissions
        $this->assertTrue($role_identity->deny('edit_users'));
        $this->assertTrue($user_identity->deny('promote_user'));

        // Confirm that identities are properly protected
        $this->assertTrue($service->is_denied_to(
            AAM::api()->roles->role('editor'), 'edit_users'
        ));
        $this->assertFalse($service->is_allowed_to(
            AAM::api()->roles->role('editor'), 'edit_users'
        ));
        $this->assertTrue($service->is_denied_to(
            AAM::api()->users->user($user_a), 'promote_user'
        ));
        $this->assertFalse($service->is_allowed_to(
            AAM::api()->users->user($user_a), 'promote_user'
        ));

        // Verify that permissions are properly inherited
        $service = AAM::api()->identities('user:' . $user_b);

        // Confirm that identities are properly protected
        $this->assertTrue($service->is_denied_to(
            AAM::api()->roles->role('editor'), 'edit_users'
        ));
        $this->assertFalse($service->is_allowed_to(
            AAM::api()->roles->role('editor'), 'edit_users'
        ));
        $this->assertTrue($service->is_denied_to(
            AAM::api()->users->user($user_a), 'promote_user'
        ));
        $this->assertFalse($service->is_allowed_to(
            AAM::api()->users->user($user_a), 'promote_user'
        ));
    }

}