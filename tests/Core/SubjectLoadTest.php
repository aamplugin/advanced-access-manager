<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;


/**
 * Test if proper subject is picked correctly
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SubjectLoadTest extends TestCase
{

    use ResetTrait;

    /**
     * Test that AAM loaded Visitor subject
     *
     * AAM has to load Visitor subject when there is no indicators or authentication
     */
    public function testLoadedVisitorType()
    {
        $subject = AAM::getUser();

        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));
    }

    /**
     * Making sure that AAM returns correct object when user is switched
     *
     * @return void
     *
     * @access public
     * @version 6.0.4
     */
    public function testCorrectObjectOnSubjectSwitch()
    {
        // Set existing user
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        // Define dummy access settings for a post
        $post = AAM::getUser()->getObject(
            \AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );
        $post->updateOptionItem('restricted', true);

        $this->assertTrue($post->is('restricted'));

        // Change user to visitor
        wp_set_current_user(0);

        $subject = AAM::getUser();
        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));

        $post = $subject->getObject(
            \AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );
        $this->assertFalse($post->is('restricted'));
    }

    /**
     * Making sure that AAM reflects changes to the current user
     *
     * @return void
     *
     * @access public
     * @version 6.0.4
     */
    public function testSubjectSwitch()
    {
        $subject = AAM::getUser();
        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));

        // Set existing user
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);

        $subject = AAM::getUser();
        $this->assertSame('AAM_Core_Subject_User', get_class($subject));
        $this->assertEquals(AAM_UNITTEST_AUTH_USER_ID, $subject->getId());

        // Reset to default
        wp_set_current_user(0);
    }

}