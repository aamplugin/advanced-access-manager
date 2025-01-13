<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Utility;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test AAM Framework manager class
 */
final class ManagerTest extends TestCase
{

    /**
     * Testing that we can properly adjust user capabilities with access policies
     *
     * @return void
     */
    // public function testUserCapabilitiesAdjustment()
    // {
    //     $user_a = $this->createUser();

    //     // Creating a new policy that grants a custom capability to a user and
    //     // removes "read" capability
    //     $this->assertIsInt(AAM::api()->policies('role:subscriber')->create('{
    //         "Statement": [
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "Capability:read"
    //             },
    //             {
    //                 "Effect": "allow",
    //                 "Resource": "Capability:aam_custom_cap"
    //             }
    //         ]
    //     }'));

    //     // Setting current user
    //     wp_set_current_user($user_a);

    //     // Verify that read is not allowed && custom cap is granted
    //     $current_user = wp_get_current_user();

    //     $this->assertTrue($current_user->caps['aam_custom_cap']);
    //     $this->assertTrue($current_user->allcaps['aam_custom_cap']);
    //     $this->assertArrayNotHasKey('read', $current_user->caps);
    //     $this->assertArrayNotHasKey('read', $current_user->allcaps);
    // }

    /**
     * Testing that we can properly adjust user roles with access policies
     *
     * @return void
     */
    public function testUserRolesAdjustment()
    {
        $user_a = $this->createUser();

        // Creating a new policy that grants a custom capability to a user and
        // removes "read" capability
        $this->assertIsInt(AAM::api()->policies('role:subscriber')->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Role:subscriber"
                },
                {
                    "Effect": "allow",
                    "Resource": "Role:author"
                }
            ]
        }'));

        // Setting current user
        wp_set_current_user($user_a);

        // Verify that read is not allowed && custom cap is granted
        $current_user = wp_get_current_user();

        $this->assertEquals([ 'author' ], $current_user->roles);
    }

}