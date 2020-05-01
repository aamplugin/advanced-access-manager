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
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM access settings inheritance mechanism for the Metaboxes & Widgets service
 * for the visitor subject
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class VisitorInheritanceTest extends TestCase
{
    use ResetTrait;

    /**
     * Test to insure that access settings are stored property on the Visitor level
     *
     * A. Test that metabox is stored to the database with "true" flag and true
     *    is returned by AAM_Core_Subject_Visitor::updateOption method;
     * B. Test that information is actually stored property in the database and can
     *    be retrieved successfully.
     *
     * @return void
     *
     * @access public
     * @see AAM_Core_Subject_Visitor::updateOption
     * @version 6.0.0
     */
    public function testSaveMetaboxOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Make sure that we actually are dealing with Visitor subject
        $this->assertEquals('AAM_Core_Subject_Visitor', get_class($user));

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
     * Test that access settings are inherited from the parent default subject
     *
     * This test is designed to verify that access settings are propagated property
     * from the default settings
     *
     * A. Test that settings can be stored for the default subject
     * B. Test that access settings are propagated property to the Visitor level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromDefault()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();
        $object = $parent->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Make sure that we work with Default subject
        $this->assertEquals('AAM_Core_Subject_Default', get_class($parent));

        // Save access settings for the Default and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  widgets|wp_widget_media_video => true
        // )
        $option = $parent->readOption(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(array('widgets|wp_widget_media_video' => true), $option);

        // Finally verify that access settings are propagated property to the Visitor
        // Level
        $metabox = $user->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array('widgets|wp_widget_media_video' => true), $metabox->getOption()
        );
    }

    /**
     * Test that access settings are propagated and merged properly
     *
     * The test is designed to verify that access settings are propagated properly
     * from the Default and merged well with explicitly defined access settings on
     * the Visitor level.
     *
     * A. Test that access settings are stored for the Default subject;
     * B. Test that access settings are stored for the Visitor;
     * C. Test that access settings are propagated and merged properly;
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromDefault()
    {
        $visitor = AAM::getUser();
        $default = $visitor->getParent();

        $object  = $default->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Save access settings for the Default and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Save access setting for the Visitor and make sure they are saved property
        $metabox = $visitor->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE, null, true);
        $this->assertTrue($metabox->updateOptionItem('widgets|wp_widget_media_image', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $metabox = $visitor->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $this->assertSame(
            array(
                'widgets|wp_widget_media_video' => true,
                'widgets|wp_widget_media_image' => false
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
     * A. Assert that access settings are stored properly for the parent subject;
     * B. Assert that access settings are stored properly for the Visitor;
     * C. Assert that access settings are overwritten properly on the Visitor Level;
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceOverride()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();

        $object = $parent->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Save access settings for the Default and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('widgets|wp_widget_media_video', true)->save());

        // Save access setting for the Visitor and make sure they are saved property
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