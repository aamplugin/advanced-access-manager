<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AdminMenu;

use AAM,
    AAM_Core_Object_Menu,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Admin Menu service
 *
 * Admin Menu is available only for authenticated users so no Visitors are tested
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class MultipleRoleInheritanceTest extends TestCase
{
    use ResetTrait;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multi_access_levels', true
        );

        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_MULTIROLE_USER_ID);
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
     * Test that access settings are inherited from multiple parent roles
     *
     * This test is designed to verify that access settings are propagated property
     * when there access settings defined for multiple parent roles.
     *
     * A. Test that settings can be stored for the parent roles;
     * B. Test that access settings are propagated property to the User level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromMultipleRoles()
    {
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multi_access_levels', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $object = $role->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);
        $this->assertTrue($object->store('index.php?id=0', true));

        foreach($role->getSiblings() as $i => $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue($sibling->getObject(
                AAM_Core_Object_Menu::OBJECT_TYPE)->store(
                    'index.php?id=' . ($i + 1), ($i % 2 ? true : false)
                )
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  index.php?id=0 => true,
        //  index.php?id=1 => false
        // )
        $option = $user->getObject(AAM_Core_Object_Menu::OBJECT_TYPE)->getOption();

        $this->assertSame(
            array('index.php?id=0' => true, 'index.php?id=1' => false), $option
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
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multi_access_levels', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true)->updateOptionItem(
                'index.php', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true)->updateOptionItem(
                    'index.php', false
                )->save()
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  index.php => true
        // )
        $option = $user->getObject(AAM_Core_Object_Menu::OBJECT_TYPE)->getOption();
        $this->assertSame(array('index.php' => true), $option);
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
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multi_access_levels', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true)->updateOptionItem(
                'index.php', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Menu::OBJECT_TYPE)->store(
                    'index.php', false
                )
            );
        }

        // Override the default "deny" precedence
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.menu.merge.preference',
            'allow'
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  index.php => false
        // )
        $option = $user->getObject(AAM_Core_Object_Menu::OBJECT_TYPE)->getOption();
        $this->assertSame(array('index.php' => false), $option);
    }

    /**
     * Test that access settings are merged correctly with "allowed" precedence
     * when explicit settings are defined for an individual user
     *
     * For more information refer to the Issue #152
     * https://github.com/aamplugin/advanced-access-manager/issues/152
     *
     * @return void
     * @version 6.7.0
     */
    public function testInheritanceAllowPrecedenceFromUserWithMultipleRoles()
    {
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multi_access_levels', true
        );

        $user = AAM::getUser();

        // Set explicit setting for individual user
        $this->assertTrue(
            $user->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true)->updateOptionItem(
                'index.php', true
            )->save()
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  index.php => true
        // )
        $option = $user->getObject(AAM_Core_Object_Menu::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array('index.php' => true), $option
        );
    }

}