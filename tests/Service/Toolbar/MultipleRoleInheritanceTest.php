<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Toolbar;

use AAM,
    AAM_Core_Config,
    AAM_Core_Object_Toolbar,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthMultiRoleUserTrait,
    AAM\UnitTest\Libs\MultiRoleOptionInterface;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Admin Toolbar service
 *
 * Admin Toolbar is available only for authenticated users so no Visitors are tested
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class MultipleRoleInheritanceTest extends TestCase implements MultiRoleOptionInterface
{
    use ResetTrait,
        AuthMultiRoleUserTrait;

    /**
     * Test that access settings are inherited from multiple parent roles
     *
     * This test is designed to verify that access settings are propagated property
     * when there access settings defined for multiple parent roles.
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromMultipleRoles()
    {
        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $object = $role->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true);
        $this->assertTrue($object->updateOptionItem('new-page', true)->save());

        foreach($role->getSiblings() as $i => $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true)->updateOptionItem(
                    'new-page-' . ($i + 1), ($i % 2 ? true : false)
                )->save()
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  new-page   => true,
        //  new-page-1 => false
        // )
        $option = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array('new-page' => true, 'new-page-1' => false), $option
        );
    }

    /**
     * Test that access settings are merged with default "deny" precedence correctly
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceDenyPrecedenceFromMultipleRoles()
    {
        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true)->updateOptionItem(
                'new-page', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true)->updateOptionItem(
                    'new-page', false
                )->save()
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  new-page => true
        // )
        $option = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array('new-page' => true), $option
        );
    }

    /**
     * Test that access settings are merged correctly with "allowed" precedence
     * correctly
     *
     * @return void
     * @version 6.0.0
     */
    public function testInheritanceAllowPrecedenceFromMultipleRoles()
    {
        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true)->updateOptionItem(
                'new-page', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE, null, true)->updateOptionItem(
                    'new-page', false
                )->save()
            );
        }

        // Override the default "deny" precedence
        AAM_Core_Config::set(
            sprintf('core.settings.%s.merge.preference', AAM_Core_Object_Toolbar::OBJECT_TYPE),
            'allow'
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  new-page => false
        // )
        $option = $user->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE)->getOption();
        $this->assertSame(array('new-page' => false), $option);
    }

}