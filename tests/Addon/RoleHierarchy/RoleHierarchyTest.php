<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\RoleHierarchy;

use AAM,
    AAM_Core_Object_Menu,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test cases for the Role Hierarchy addon
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class RoleHierarchyTest extends TestCase
{
    use ResetTrait;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);
    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

    /**
     * Test that role can have a parent role and settings are propagated properly
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRoleInheritance()
    {
        $contributor = AAM::api()->getRole('contributor');
        $object      = $contributor->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        // Set fake settings for the Contributor
        $this->assertTrue($object->updateOptionItem('edit.php', true)->save());

        // Fake the fact that Subscriber has a parent role Contributor
        AAM_Framework_Manager::configs()->set_config(
            'system.role.subscriber.parent', 'contributor'
        );

        // Reset all internal cache
        $this->_resetSubjects();

        $subscriber = AAM::api()->getRole('subscriber');
        $object     = $subscriber->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        $this->assertEquals('contributor', $subscriber->getParent()->getId());
        $this->assertTrue($object->isRestricted('edit.php'));
    }

}