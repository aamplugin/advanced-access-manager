<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use AAM,
    AAM_Service_Content,
    AAM_Core_Object_Post,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test that content access settings are applied and used properly with WordPress core
 * for the unauthorized user
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class VisitorAccessControlTest extends TestCase
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
     * Targeting page ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $page_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Content Service Post',
            'post_name'   => 'content-service-post',
            'post_status' => 'publish'
        ));

        // Setup a default page
        self::$page_id = wp_insert_post(array(
            'post_title'  => 'Content Service Page',
            'post_name'   => 'content-service-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));
    }

    /**
     * Test that visitor is not allowed to access the post when access settings
     * are set so on the Visitor Level
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRestrictedOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('restricted', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Make sure that AAM API returns correct result
        $this->assertTrue($post->is('restricted'));

        // Check that current user is not allowed to read_post
        $this->assertFalse(current_user_can('read_post', self::$post_id));
    }

    /**
     * Test that visitor does not have the ability to see hidden post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testHiddenOption()
    {
        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => 100,
            'suppress_filters' => false
        ));

        // First, confirm that post is in the array of posts
        $this->assertTrue(in_array(self::$post_id, $posts));

        // Hide the post
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that post is no longer in the list of posts
        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'suppress_filters' => false
        ));

        // First, confirm that post is in the array of posts
        $this->assertFalse(in_array(self::$post_id, $posts));
    }

    /**
     * Test that content is limited with the Teaser message and enabled excerpt
     * shortcode
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTeaserMessageOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('teaser', array(
            'enabled' => true,
            'message' => 'Test teaser with [excerpt]'
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Confirm that teaser message is returned instead of actual content
        $GLOBALS['post'] = self::$post_id;
        ob_start();
        the_content();
        $this->assertSame(
            ob_get_contents(), 'Test teaser with ' . $object->post_excerpt
        );
        ob_end_clean();
    }

    /**
     * Test that visitor does not have the ability to comment on a post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCommentingOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Verify that commenting for this feature is set as open
        $this->assertEquals($object->comment_status, 'open');

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('comment', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // First, confirm that post is in the array of posts
        $this->assertFalse(comments_open(self::$post_id));
    }

    /**
     * Test that REDIRECTED to Existing Page option is working as expected
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectPageOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'page',
            'destination' => self::$page_id,
            'httpCode'    => 301
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'type'   => 'url',
            'url'    => get_page_link(self::$page_id),
            'status' => 301
        ), $response->get_error_data());
    }

    /**
     * Test that REDIRECTED to URL option is working as expected
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectURLOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'url',
            'destination' => 'https://aamportal.com',
            'httpCode'    => 307
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'type'   => 'url',
            'url'    => 'https://aamportal.com',
            'status' => 307
        ), $response->get_error_data());
    }

    /**
     * Test that REDIRECTED to PHP Callback option is working as expected
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectCallbackOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'callback',
            // WordPress core strips slashes, so we have to double slash all this
            'destination' => 'AAM\\UnitTest\\Service\\Content\\Callback::redirectCallback',
            'httpCode'    => 310
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'type'   => 'url',
            'url'    => Callback::REDIRECT_URL,
            'status' => 310
        ), $response->get_error_data());
    }

    /**
     * Test that REDIRECTED to Login Page option is working as expected
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectLoginOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'login',
            'httpCode'    => 301
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'type'   => 'url',
            'url'    => wp_login_url() . '?reason=restricted',
            'status' => 301
        ), $response->get_error_data());
    }

    /**
     * Test PASSWORD PROTECTED option
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testProtectedOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('protected', array(
            'enabled'  => true,
            'password' => '123456'
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Get post
        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Verify that password is required
        $this->assertTrue(
            apply_filters('post_password_required', false, get_post(self::$post_id))
        );

        // Verify that password is not required when explicitly provided
        $this->assertTrue(
            AAM_Service_Content::getInstance()->checkPostPassword($post, '123456')
        );

        // Test that password is required when incorrect password is provided
        $this->assertEquals(
            'WP_Error',
            get_class(AAM_Service_Content::getInstance()->checkPostPassword($post, '654321'))
        );
    }

    /**
     * Test CEASED option
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCeasedOption()
    {
        // Hide the post
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('ceased', array(
            'enabled' => true,
            'after'   => time() - 86000
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Get post
        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Verify that access to the post is expired
        $error = AAM_Service_Content::getInstance()->checkPostExpiration($post);

        $this->assertEquals('WP_Error', get_class($error));
        $this->assertEquals(
            'User is unauthorized to access this post. Access Expired.',
            $error->get_error_message()
        );

        // Test that password is required when incorrect password is provided
        $this->assertEquals(
            'WP_Error',
            get_class(AAM_Service_Content::getInstance()->isAuthorizedToReadPost($post))
        );
    }

}