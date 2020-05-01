<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Metabox;

use AAM,
    AAM_Core_Config,
    AAM_Core_Object_Metabox,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthMultiRoleUserTrait,
    AAM\UnitTest\Libs\MultiRoleOptionInterface;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Metaboxes & Widgets service
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
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                'dashboard|dashboard_quick_press_0', true
            )->save()
        );

        foreach($role->getSiblings() as $i => $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                    'dashboard|dashboard_quick_press_' . ($i + 1), ($i % 2 ? true : false)
                )->save()
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  dashboard|dashboard_quick_press_0 => true,
        //  dashboard|dashboard_quick_press_1 => false
        // )
        $option = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array(
                'dashboard|dashboard_quick_press_0' => true,
                'dashboard|dashboard_quick_press_1' => false
            ),
            $option
        );
    }

    /**
     * Check that access to resource is denied when two or more roles have the same
     * resource defined
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
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                'widgets|wp_widget_media_video', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                    'widgets|wp_widget_media_video', false
                )->save()
            );
        }

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  widgets|wp_widget_media_video => true
        // )
        $option = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array('widgets|wp_widget_media_video' => true), $option
        );
    }

    /**
     * Check that access is allowed to the resource when two or more roles have the
     * same resource defined
     *
     * @return void
     *
     * @access public
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
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                'widgets|wp_widget_media_video', true
            )->save()
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->updateOptionItem(
                    'widgets|wp_widget_media_video', false
                )->save()
            );
        }

        // Override the default "deny" precedence
        AAM_Core_Config::set(
            sprintf('core.settings.%s.merge.preference', AAM_Core_Object_Metabox::OBJECT_TYPE),
            'allow'
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  widgets|wp_widget_media_video => false
        // )
        $option = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE)->getOption();
        $this->assertSame(
            array('widgets|wp_widget_media_video' => false), $option
        );
    }

}