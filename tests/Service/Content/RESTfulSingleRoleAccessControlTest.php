<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use AAM,
    WP_REST_Request,
    AAM_Service_Content,
    AAM_Core_Object_Post,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test that content access settings through the WP RESTful API
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class RESTfulSingleRoleAccessControlTest extends TestCase
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('post_access_restricted', $data['code']);
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
        $server = rest_get_server();

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
        $request = new WP_REST_Request('GET', '/wp/v2/posts');
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        // First, confirm that post is in the array of posts
        $this->assertCount(0, array_filter($data, function($post) {
            return $post['id'] === AAM_UNITTEST_POST_ID;
        }));
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
        $server = rest_get_server();
        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertSame(
            $data['content']['rendered'], 'Test teaser with ' . $object->post_excerpt
        );
    }

    /**
     * Test the LIMITED option
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLimitedOption()
    {
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('post_access_exceeded_limit', $data['code']);
    }

    /**
     * Test that view counter is incremented after each view
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLimitedIncrementedCounterOption()
    {
        // Limit the post
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('limited', array(
            'enabled'   => true,
            'threshold' => 10
        ))->save());

        // Tracking key
        $key = sprintf(AAM_Service_Content::POST_COUNTER_DB_OPTION, AAM_UNITTEST_POST_ID);

        // Faking the fact that user already seen this post once
        update_user_option(AAM_UNITTEST_AUTH_USER_ID, $key, 1);

        // Reset all internal cache
        $this->_resetSubjects();

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $status = $server->dispatch($request)->get_status();

        $this->assertEquals(200, $status);
        $this->assertEquals(2, get_user_option($key, AAM_UNITTEST_AUTH_USER_ID));
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

        $server = rest_get_server();

        $request = new WP_REST_Request('POST', '/wp/v2/comments');
        $request->set_param('post', AAM_UNITTEST_POST_ID);
        $request->set_param('content', 'Test comment');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('rest_comment_closed', $data['code']);
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('post_access_redirected', $data['code']);
        $this->assertEquals(get_page_link(AAM_UNITTEST_PAGE_ID), $data['url']);
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('post_access_redirected', $data['code']);
        $this->assertEquals('https://aamplugin.com', $data['url']);
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('post_access_redirected', $data['code']);
        $this->assertEquals(Callback::REDIRECT_URL, $data['url']);
    }

    /**
     * Test PASSWORD PROTECTED option when password is enforced by AAM and is valid
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAAMEnforcedPasswordValidOption()
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');
        $request->set_param('password', '123456');

        $this->assertEquals(200, $server->dispatch($request)->get_status());
    }

    /**
     * Test PASSWORD PROTECTED option when password is enforced by AAM and is invalid
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAAMEnforcedPasswordInvalidOption()
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');
        $request->set_param('password', 'abs');

        $response = $server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        $this->assertEquals('post_access_protected', $response->get_data()['code']);
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

        $server = rest_get_server();

        $request = new WP_REST_Request('GET', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('context', 'view');

        $response = $server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        $this->assertEquals('post_access_expired', $response->get_data()['code']);
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

        $server = rest_get_server();

        $request = new WP_REST_Request('POST', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('content', 'Test');

        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_edit', $response->get_data()['code']);
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

        $server = rest_get_server();

        $request  = new WP_REST_Request('DELETE', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_delete', $response->get_data()['code']);
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

        $server = rest_get_server();

        $request  = new WP_REST_Request('POST', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('status', 'publish');
        $response = $server->dispatch($request);

        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cannot_publish', $response->get_data()['code']);
    }

}