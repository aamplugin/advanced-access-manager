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
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test cases for the Role Hierarchy addon
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class RoleHierarchyTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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
        $this->assertTrue($object->updateOptionItem('index.php', true)->save());

        // Fake the fact that Subscriber has a parent role Contributor
        AAM::api()->updateConfig('system.role.subscriber.parent', 'contributor');

        // Reset all internal cache
        $this->_resetSubjects();

        $subscriber = AAM::api()->getRole('subscriber');
        $object     = $subscriber->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        $this->assertEquals('contributor', $subscriber->getParent()->getId());
        $this->assertTrue($object->isRestricted('index.php'));
    }

}