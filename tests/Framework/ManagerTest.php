<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework;

use AAM,
    AAM_Framework_Manager,
    AAM\UnitTest\Utility\TestCase;

/**
 * Framework manager test
 */
final class ManagerTest extends TestCase
{

    /**
     * Testing default framework context
     *
     * Making sure that the default context is updated accordingly and new
     * current user is set as the default access level
     *
     * @return void
     */
    public function testDefaultContext() : void
    {
        $user_id = $this->createUser([ 'role' => 'administrator' ]);

        // Setting current user
        wp_set_current_user($user_id);

        // Just user any service and do not provide runtime context. This will
        // force the framework to use the default context
        $service = AAM_Framework_Manager::access_denied_redirect();

        $this->assertEquals($service->access_level->ID, $user_id);
    }

    /**
     * Testing runtime (aka inline) context
     *
     * Making sure that the default context is not taken into consideration when
     * inline context provided
     *
     * @return void
     */
    public function testRuntimeContext() : void
    {
        $user_id_a = $this->createUser([ 'role' => 'subscriber' ]);

        // Setting new current user. The default context will be updated to this
        // user
        wp_set_current_user($user_id_a);

        // Creating new user and use the inline context instead
        $user_id_b = $this->createUser([ 'role' => 'subscriber' ]);

        // Just user any service and do not provide runtime context. This will
        // force the framework to use the default context
        $service = AAM_Framework_Manager::access_denied_redirect([
            'access_level' => AAM::api()->user($user_id_b)
        ]);

        $this->assertEquals($service->access_level->ID, $user_id_b);
    }

}