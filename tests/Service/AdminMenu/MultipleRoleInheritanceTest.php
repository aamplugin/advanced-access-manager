<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AdminMenu;

use AAM,
    AAM_Core_Config,
    AAM_Core_Object_Menu,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\MultiRoleOptionInterface;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Admin Menu service
 *
 * Admin Menu is available only for authenticated users so no Visitors are tested
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class MultipleRoleInheritanceTest extends TestCase implements MultiRoleOptionInterface
{
    use ResetTrait;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        if (is_subclass_of(self::class, 'AAM\UnitTest\Libs\MultiRoleOptionInterface')) {
            // Enable Multiple Role Support
            AAM_Core_Config::set('core.settings.multiSubject', true);
        }

        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_MULTIROLE_USER_ID);

        // Override AAM current user
        AAM::getInstance()->setUser(
            new \AAM_Core_Subject_User(AAM_UNITTEST_MULTIROLE_USER_ID)
        );
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
        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $object = $role->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true);
        $this->assertTrue($object->updateOptionItem('index.php?id=0', true)->save());

        foreach($role->getSiblings() as $i => $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Menu::OBJECT_TYPE, null, true)->updateOptionItem(
                    'index.php?id=' . ($i + 1), ($i % 2 ? true : false)
                )->save()
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
        $this->assertSame(
            array('index.php' => true), $option
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

        // Override the default "deny" precedence
        AAM_Core_Config::set(
            sprintf('core.settings.%s.merge.preference', AAM_Core_Object_Menu::OBJECT_TYPE),
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