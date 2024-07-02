<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Metabox;

use AAM,
    AAM_Framework_Manager,
    AAM_Core_Object_Metabox,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Metaboxes & Widgets service
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
            'core.settings.multiSubject', true
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
            'core.settings.multiSubject', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                'dashboard|dashboard_quick_press_0', true
            )
        );

        foreach($role->getSiblings() as $i => $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                    'dashboard|dashboard_quick_press_' . ($i + 1), ($i % 2 ? true : false)
                )
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
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multiSubject', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                'widgets|wp_widget_media_video', true
            )
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                    'widgets|wp_widget_media_video', false
                )
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
        // Enable multi-role support
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.multiSubject', true
        );

        $user = AAM::getUser();
        $role = $user->getParent();

        // Make sure that we have parent roles defined properly
        $this->assertEquals('AAM_Core_Subject_Role', get_class($role));

        // Save access settings for the base role and iterate over each sibling and
        // add additional settings
        $this->assertTrue(
            $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                'widgets|wp_widget_media_video', true
            )
        );

        foreach($role->getSiblings() as $sibling) {
            // Save access settings for each role and make sure they are saved property
            // Check if save returns positive result
            $this->assertTrue(
                $sibling->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true)->store(
                    'widgets|wp_widget_media_video', false
                )
            );
        }

        // Override the default "deny" precedence
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.metabox.merge.preference',
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