<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use AAM,
    AAM_Core_API,
    AAM_Service_Content,
    AAM_Core_Object_Post,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test that content access settings are applied and used properly with WordPress core
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SingleRoleAccessControlTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

    /**
     * Test that user is not allowed to access the post when access settings are set
     * so on the User Level
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('restricted', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Make sure that AAM API returns correct result
        $this->assertTrue($post->is('restricted'));

        // Check that current user is not allowed to read_post
        $this->assertFalse(current_user_can('read_post', AAM_UNITTEST_POST_ID));
    }

    /**
     * Test that user does not have the ability to see hidden post
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
        $this->assertTrue(in_array(AAM_UNITTEST_POST_ID, $posts));

        // Hide the post
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
        $this->assertFalse(in_array(AAM_UNITTEST_POST_ID, $posts));
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('teaser', array(
            'enabled' => true,
            'message' => 'Test teaser with [excerpt]'
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Confirm that teaser message is returned instead of actual content
        $GLOBALS['post'] = AAM_UNITTEST_POST_ID;
        ob_start();
        the_content();
        $this->assertSame(
            ob_get_contents(), 'Test teaser with ' . $object->post_excerpt
        );
        ob_end_clean();
    }

    /**
     * Test the LIMITED option
     *
     * Forcing $wp_query to trigger AAM_Service_Content::wp
     *
     * @return void
     *
     * @access public
     * @see AAM_Service_Content::wp
     * @version 6.0.0
     */
    public function testLimitedOption()
    {
        global $wp_query;

        // Limit the post
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('limited', array(
            'enabled'   => true,
            'threshold' => 1
        ))->save());

        // Faking the fact that user already seen this post once
        update_user_option(
            AAM_UNITTEST_AUTH_USER_ID,
            sprintf(AAM_Service_Content::POST_COUNTER_DB_OPTION, AAM_UNITTEST_POST_ID),
            1
        );

        // Reset all internal cache
        $this->_resetSubjects();

        // Forcing WP_Query to the right path
        $wp_query->is_single = true;
        $GLOBALS['post'] = get_post(AAM_UNITTEST_POST_ID);

        // Override the default handlers so we can suppress die exit
        add_filter('wp_die_handler', function() {
            return function($message, $title) {
                _default_wp_die_handler($message, $title, array('exit' => false));
            };
        }, PHP_INT_MAX);

        // Capture the WP Die message
        ob_start();
        do_action('wp');
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString(
            'User exceeded allowed access number. Access denied.', $content
        );

        // Reset WP Query
        remove_all_filters('wp_die_handler', PHP_INT_MAX);

        $wp_query->is_single = null;
        unset($GLOBALS['post']);
    }

    /**
     * Test that user does not have the ability to comment on a post
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Verify that commenting for this feature is set as open
        $this->assertEquals($object->comment_status, 'open');

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('comment', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // First, confirm that post is in the array of posts
        $this->assertFalse(comments_open(AAM_UNITTEST_POST_ID));
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'page',
            'destination' => AAM_UNITTEST_PAGE_ID,
            'httpCode'    => 301
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'url'    => get_page_link(AAM_UNITTEST_PAGE_ID),
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('redirected', array(
            'enabled'     => true,
            'type'        => 'url',
            'destination' => 'https://aamplugin.com',
            'httpCode'    => 307
        ))->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $service  = AAM_Service_Content::getInstance();
        $response = $service->isAuthorizedToReadPost($user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'url'    => 'https://aamplugin.com',
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        ));

        // Make sure that we have WP Error
        $this->assertEquals(
            $response->get_error_message(),
            'Direct access is not allowed. Follow the provided redirect rule.'
        );

        $this->assertEquals(array(
            'url'    => Callback::REDIRECT_URL,
            'status' => 310
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Verify that password is required
        $this->assertTrue(
            apply_filters('post_password_required', false, get_post(AAM_UNITTEST_POST_ID))
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
     * Test PASSWORD PROTECTED option with passed cookie
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testProtectedWithCookieOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Verify that password is required
        $this->assertTrue(
            apply_filters('post_password_required', false, get_post(AAM_UNITTEST_POST_ID))
        );

        // Generate cookie
        $hasher = AAM_Core_API::prepareHasher();
        $_COOKIE['wp-postpass_' . COOKIEHASH] = $hasher->HashPassword('123456');

        // Verify that password is not required when explicitly provided
        $this->assertTrue(
            AAM_Service_Content::getInstance()->checkPostPassword($post)
        );

        // Test that password is required when incorrect password is provided
        $_COOKIE['wp-postpass_' . COOKIEHASH] = $hasher->HashPassword('654321');
        $this->assertEquals(
            'WP_Error',
            get_class(AAM_Service_Content::getInstance()->checkPostPassword($post))
        );

        // Reset
        unset($_COOKIE['wp-postpass_' . COOKIEHASH]);
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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

    /**
     * Test that user does not have the ability to edit a post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEditOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Verify that editing is allowed for a specific post
        $this->assertTrue(current_user_can('edit_post', AAM_UNITTEST_POST_ID));

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('edit', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that user is no longer allowed to edit a post
        $this->assertFalse(current_user_can('edit_post', AAM_UNITTEST_POST_ID));
    }

    /**
     * Test that user does not have the ability to delete a post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDeleteOption()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Verify that deletion is allowed for a specific post
        $this->assertTrue(current_user_can('delete_post', AAM_UNITTEST_POST_ID));

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('delete', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that user is no longer allowed to delete a post
        $this->assertFalse(current_user_can('delete_post', AAM_UNITTEST_POST_ID));
    }

    /**
     * Test that user does not have the ability to publish a post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPublishOption()
    {
        global $post;

        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Force global post
        $post = get_post(AAM_UNITTEST_POST_ID);

        // Verify that publishing is allowed for a specific post
        $this->assertTrue(current_user_can('publish_post', AAM_UNITTEST_POST_ID));

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('publish', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Verify that user is no longer allowed to publish a post
        $this->assertFalse(current_user_can('publish_post', AAM_UNITTEST_POST_ID));

        // Reset to default the global state
        unset($post);
    }

}