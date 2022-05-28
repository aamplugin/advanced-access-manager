<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Libs;

use AAM,
    AAM_Core_Config,
    AAM_Core_Subject_User;

/**
 * Trait that setup multi-role support
 *
 * The `AAM_UNITTEST_AUTH_MULTIROLE_USER_ID` constant that is defined in the main
 * phpunit.xml.dist config, has to point to the existing WP user that has more than
 * one role assigned
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
trait AuthMultiRoleUserTrait
{

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass() : void
    {
        if (is_subclass_of(self::class, 'AAM\UnitTest\Libs\MultiRoleOptionInterface')) {
            // Enable Multiple Role Support
            AAM_Core_Config::set('core.settings.multiSubject', true);
        }

        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_MULTIROLE_USER_ID);

        // Override AAM current user
        AAM::getInstance()->setUser(
            new AAM_Core_Subject_User(AAM_UNITTEST_MULTIROLE_USER_ID)
        );
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass() : void
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

}