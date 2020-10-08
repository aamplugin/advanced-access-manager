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
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test AAM access settings inheritance mechanism for the Content (Posts & Terms)
 * service
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SingleRoleInheritanceTest extends TestCase
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
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

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
     * Test to insure that access settings are stored property on the User level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testSaveUserLevelOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  hidden => true
        // )
        $option = $user->readOption(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id . '|post'
        );

        $this->assertSame(array('hidden' => true), $option);
    }

    /**
     * Test that access settings are inherited from the parent role property
     *
     * This test is designed to verify that access settings are propagated property
     * when there is only one role assigned to a user.
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
        $object = $parent->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Make sure that we have parent role defined
        $this->assertEquals('AAM_Core_Subject_Role', get_class($parent));

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Read from the database saved values and assert that we have
        // Array (
        //  hidden => true
        // )
        $option = $parent->readOption(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id . '|post'
        );
        $this->assertSame(array('hidden' => true), $option);

        // Finally verify that access settings are propagated property to the User
        // Level
        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );
        $this->assertSame(array('hidden' => true), $post->getOption());
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
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceMergeFromSingleRole()
    {
        $user   = AAM::getUser();
        $parent = $user->getParent();
        $object = $parent->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Save access setting for the user and make sure they are saved property
        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true
        );
        $this->assertTrue($post->updateOptionItem('comment', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );
        $this->assertSame(
            array('hidden' => true, 'comment' => false),
            $post->getOption()
        );
    }

    /**
     * Test that the full inheritance mechanism is working as expected
     *
     * Make sure that access settings are propagated and merged properly from the top
     * (Default Level) to the bottom (User Level).
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

        $userPost    = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true);
        $rolePost    = $role->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true);
        $defaultPost = $default->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true);

        // Save access settings for all subjects
        $this->assertTrue($userPost->updateOptionItem('hidden', true)->save());
        $this->assertTrue($rolePost->updateOptionItem('comment', true)->save());
        $this->assertTrue($defaultPost->updateOptionItem('restricted', true)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        // All settings has to be merged into one array
        $post = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id);
        $this->assertSame(
            array(
                'restricted' => true,
                'comment' => true,
                'hidden' => true
            ),
            $post->getOption()
        );
    }

    /**
     * Test that access settings overwrite works as expected
     *
     * The expected result is lower Access Level overwrite access settings from the
     * higher Access Level.
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

        $object = $parent->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id);

        // Save access settings for the role and make sure they are saved property
        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Save access setting for the user and make sure they are saved property
        $post = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id, true);
        $this->assertTrue($post->updateOptionItem('hidden', false)->save());

        // Reset cache and try to kick-in the inheritance mechanism
        $this->_resetSubjects();

        $post = $user->getObject(AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id);
        $this->assertSame(array('hidden' => false), $post->getOption());
    }

}