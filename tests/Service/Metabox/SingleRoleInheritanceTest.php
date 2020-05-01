<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Metabox;

use AAM,
    AAM_Core_Object_Metabox,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test AAM access settings inheritance mechanism for the Metaboxes & Widgets service
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SingleRoleInheritanceTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

    /**
     * Test to insure that access settings are stored property on the User level
     *
     * A. Test that metabox is stored to the database with "true" flag and true
     *    is returned by AAM_Core_Subject_User::updateOption method;
     * B. Test that information is actually stored property in the database and can
     *    be retrieved successfully.
     *
     * @return void
     *
     * @access public
     * @see AAM_Core_Subject_User::updateOption
     * @version 6.0.0
     */
    public function testSaveMetaboxOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  widgets|wp_widget_media_video => true
        // )
        $option = $user->readOption(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(array('widgets|wp_widget_media_video' => true), $option);
    }

    /**
     * Test that access settings are inherited from the parent role property
     *
     * This test is designed to verify that access settings are propagated property
     * when there is only one role assigned to a user.
     *
     * A. Test that settings can be stored for the parent role;
     * B. Test that access settings are propagated property to the User level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();
        $object = $parent->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Make sure that we have parent role defined
        $this->assertEquals('AAM_Core_Subject_Role', get_class($parent));

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('dashboard|dashboard_quick_press', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  dashboard|dashboard_quick_press => true
        // )
        $option = $parent->readOption(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(array('dashboard|dashboard_quick_press' => true), $option);

        // Finally verify that access settings are propagated property to the User
        // Level
        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array('dashboard|dashboard_quick_press' => true), $metabox->getOption()
        );
    }

    /**
     * Test that access settings are propagated and merged properly
     *
     * The test is designed to verify that access settings are propagated properly
     * from the parent role and merged well with explicitly defined access settings on
     * the User level.
     *
     * The expected result is to have combined array of access settings from the parent
     * role and specific user.
     *
     * A. Test that access settings are stored for the parent role;
     * B. Test that access settings are stored for the user;
     * C. Test that access settings are propagated and merged properly;
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();

        $object = $parent->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Save access setting for the user and make sure they are saved property
        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);
        $this->assertTrue($metabox->updateOptionItem('dashboard|dashboard_quick_press', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array(
                'widgets|wp_widget_media_video' => true,
                'dashboard|dashboard_quick_press' => false
            ),
            $metabox->getOption()
        );
    }

    /**
     * Test that the full inheritance mechanism is working as expected
     *
     * Make sure that access settings are propagated and merged properly from the top
     * (Default Level)to the bottom (User Level).
     *
     * A. Assert that access settings are stored properly for each Access Level;
     * B. Assert that access settings are merged properly and assigned to User Level;
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testFullInheritanceChainSingeRole()
    {
        $user    = AAM::getUser();
        $role    = $user->getParent();
        $default = $role->getParent();

        $userMetabox    = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);
        $roleMetabox    = $role->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);
        $defaultMetabox = $default->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);

        // Save access settings for all subjects
        $this->assertTrue($userMetabox->updateOptionItem('widgets|wp_widget_media_video', true)->save());
        $this->assertTrue($roleMetabox->updateOptionItem('dashboard|dashboard_quick_press', true)->save());
        $this->assertTrue($defaultMetabox->updateOptionItem('post|publish_post', true)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        // All settings has to be merged into one array
        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array(
                'post|publish_post' => true,
                'dashboard|dashboard_quick_press' => true,
                'widgets|wp_widget_media_video' => true
            ),
            $metabox->getOption()
        );
    }

    /**
     * Test that access settings overwrite works as expected
     *
     * The expected result is lower Access Level overwrite access settings from the
     * higher Access Level.
     *
     * A. Assert that access settings are stored properly for the parent role;
     * B. Assert that access settings are stored properly for the specific user;
     * C. Assert that access settings are overwritten properly on the User Level;
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceOverrideForSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();

        $object = $parent->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Save access setting for the user and make sure they are saved property
        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);
        $this->assertTrue($metabox->updateOptionItem('widgets|wp_widget_media_video', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array('widgets|wp_widget_media_video' => false), $metabox->getOption()
        );
    }

}