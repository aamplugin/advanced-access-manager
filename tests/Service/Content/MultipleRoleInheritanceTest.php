<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use AAM,
    AAM_Core_Object_Post,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM access settings inheritance mechanism for multiple roles per user for
 * the Content service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class MultipleRoleInheritanceTest extends TestCase
{
    use ResetTrait;

    /**
     * Targeting post ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $post_id;

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

        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Content Service Post',
            'post_name'   => 'content-service-post',
            'post_status' => 'publish'
        ));
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
        $this->assertTrue($role->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true)->store(
                'limited',
                array(
                    'enabled'   => true,
                    'threshold' => 1
                )
            )
        );

        // Set the access settings for the next Sibling
        $sibling = $role->getSiblings()[0];

        $sibling->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true
        )->store(
            'hidden',
            false
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  limited => Array (
        //   enabled   => true,
        //   threshold => 1
        //  ),
        //  hidden => false
        // )
        $object = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id);

        $this->assertSame(
            array(
                'limited' => array(
                    'enabled'   => true,
                    'threshold' => 1
                ),
                'hidden' => false
            ),
            $object->getOption()
        );
    }

    /**
     * Test that access settings are merged with default "deny" preference correctly
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceDenyPreferenceFromMultipleRoles()
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
        $this->assertTrue($role->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true)->store(
                'hidden', true
            )
        );

        // Set the access settings for the next Sibling
        $sibling = $role->getSiblings()[0];

        $sibling->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true
        )->store(
            'hidden',
            false
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  hidden => true
        // )
        $option = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id)->getOption();
        $this->assertSame(array('hidden' => true), $option);
    }

    /**
     * Test that access settings are merged with default "deny" preference correctly
     *
     * In this test, the first role will have explicitly defined access settings that
     * deny access, while the second role has no settings defined. This way the
     * expected outcome should be access allowed.
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceAllowPreferenceFromMultipleRoles()
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
        $this->assertTrue($role->getObject(
                AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true
            )->store('limited', array('enabled' => true, 'threshold' => 10))
        );

        // Override the default "deny" precedence
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.post.merge.preference',
            'allow'
        );

        // Reset internal AAM cache
        $this->_resetSubjects();

        // Assert that we have both roles merged result is as following
        // Array (
        //  limited => Array (
        //    enabled   => false,
        //    threshold => 10
        //  )
        // )
        $option = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        )->getOption();

        $this->assertSame(
            array('limited' => array('enabled' => false, 'threshold' => 10)),
            $option
        );
    }

}