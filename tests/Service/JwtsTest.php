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
 * AAM JWTs service test suite
 */
final class JwtsTest extends TestCase
{

    /**
     * Test that we can correctly determine a user based on provided token
     *
     * @return void
     */
    public function testDetermineUserThroughToken()
    {
        $user_a = $this->createUser([ 'role' => 'subscriber' ]);
        $token  = AAM::api()->jwts('user:' . $user_a)->issue();

        // Adding token to the super global
        $_POST['aam-jwt'] = $token['token'];

        $this->assertEquals($user_a, apply_filters('determine_current_user', null));

        unset($_POST['aam-jwt']);
    }

    /**
     * Test that we skip user if they are inactive
     *
     * @return void
     */
    public function testDetermineInactiveUserThroughToken()
    {
        $user_a = $this->createUser([ 'role' => 'subscriber' ]);
        $token  = AAM::api()->jwts('user:' . $user_a)->issue();

        // Get user and lock them
        $this->assertEquals(
            'inactive',
            AAM::api()->users->get_user($user_a)->update([ 'status' => 'inactive' ])->status
        );

        // Adding token to the super global
        $_POST['aam-jwt'] = $token['token'];

        $this->assertFalse(apply_filters('determine_current_user', false));

        unset($_POST['aam-jwt']);
    }

}