<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Post" framework resource
 */
final class PostTest extends TestCase
{

    /**
     * Testing Post resource - password protected permission
     *
     * @return void
     */
    public function testPostPasswordProtectedResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();
        $post_b = $this->createPost([ 'post_name' => 'another-post' ]);

        // Verifying that both posts are still allowed
        $this->assertFalse(AAM::api()->posts()->is_password_protected($post_a));
        $this->assertFalse(AAM::api()->posts()->is_password_protected($post_b));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_a . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Password": "pwd1"
                },
                {
                    "Resource": "Post:post:another-post",
                    "Effect": "deny",
                    "Action": "Read",
                    "Password": "pwd2"
                }
            ]
        }'));

        // Get the content service
        $service = AAM::api()->posts();

        // Verifying that posts are properly protected
        $this->assertTrue($service->is_password_protected($post_a));
        $this->assertEquals('pwd1', $service->get_password($post_a));
        $this->assertTrue($service->is_password_protected($post_b));
        $this->assertEquals('pwd2', $service->get_password($post_b));
    }

    /**
     * Testing Post resource - teaser message permission
     *
     * @return void
     */
    public function testPostTeasedResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_a . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Teaser": "Nope!"
                }
            ]
        }'));

        // Get the content service
        $service = AAM::api()->posts();

        // Verifying that posts are properly protected
        $this->assertTrue($service->is_teaser_message_set($post_a));
        $this->assertEquals('Nope!', $service->get_teaser_message($post_a));
    }

    /**
     * Testing Post resource - redirect
     *
     * @return void
     */
    public function testPostRedirectResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();
        $post_b = $this->createPost();
        $post_c = $this->createPost();
        $post_d = $this->createPost();
        $post_e = $this->createPost();
        $post_f = $this->createPost();

        // Verifying that both posts are still allowed
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_a));
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_b));
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_c));
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_d));
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_e));
        $this->assertFalse(AAM::api()->posts()->is_redirected($post_f));

        // Creating new policies & attaching them to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_a . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "page_redirect",
                        "Slug": "authentication-required",
                        "StatusCode": 301
                    }
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_b . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "page_redirect",
                        "Id": 76,
                        "StatusCode": 307
                    }
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_c . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "url_redirect",
                        "Url": "/different-page",
                        "StatusCode": 302
                    }
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_d . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "custom_message",
                        "Message": "You are not allowed to be here"
                    }
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_e . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "login_redirect"
                    }
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_f . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Redirect": {
                        "Type": "trigger_callback",
                        "Callback": "do_redirect_or_return_redirect_url_func"
                    }
                }
            ]
        }'));

        // Get the content service
        $service = AAM::api()->posts();

        // Verifying that posts are properly protected
        $this->assertTrue($service->is_redirected($post_a));
        $this->assertTrue($service->is_redirected($post_b));
        $this->assertTrue($service->is_redirected($post_c));
        $this->assertTrue($service->is_redirected($post_d));
        $this->assertTrue($service->is_redirected($post_e));
        $this->assertTrue($service->is_redirected($post_f));

        $this->assertEquals([
            'type'               => 'page_redirect',
            'redirect_page_slug' => 'authentication-required',
            'http_status_code'   => 301
        ], $service->get_redirect($post_a));

        $this->assertEquals([
            'type'             => 'page_redirect',
            'redirect_page_id' => 76,
            'http_status_code' => 307
        ], $service->get_redirect($post_b));

        $this->assertEquals([
            'type'             => 'url_redirect',
            'redirect_url'     => '/different-page',
            'http_status_code' => 302
        ], $service->get_redirect($post_c));

        $this->assertEquals([
            'type'    => 'custom_message',
            'message' => 'You are not allowed to be here'
        ], $service->get_redirect($post_d));

        $this->assertEquals([
            'type' => 'login_redirect',
        ], $service->get_redirect($post_e));

        $this->assertEquals([
            'type'     => 'trigger_callback',
            'callback' => 'do_redirect_or_return_redirect_url_func',
        ], $service->get_redirect($post_f));
    }

    /**
     * Testing Post resource - list permission
     *
     * @return void
     */
    public function testPostListResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();
        $post_b = $this->createPost();
        $post_c = $this->createPost();

        // Verifying that both posts are still allowed
        $this->assertFalse(AAM::api()->posts()->is_hidden($post_a));
        $this->assertFalse(AAM::api()->posts()->is_hidden($post_b));
        $this->assertFalse(AAM::api()->posts()->is_hidden($post_c));

        // Creating new policies & attaching them to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_a . '",
                    "Effect": "deny",
                    "Action": "List"
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_b . '",
                    "Effect": "deny",
                    "Action": "List",
                    "On": "frontend"
                }
            ]
        }'));

        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_c . '",
                    "Effect": "deny",
                    "Action": "List",
                    "On": [
                        "frontend",
                        "api"
                    ]
                }
            ]
        }'));

        // Get the content service
        $service = AAM::api()->posts();

        // Verifying that posts are properly protected
        $this->assertTrue($service->is_hidden($post_a));
        $this->assertTrue($service->is_hidden_on($post_a, 'frontend'));
        $this->assertTrue($service->is_hidden_on($post_a, 'backend'));
        $this->assertTrue($service->is_hidden_on($post_a, 'api'));
        $this->assertTrue($service->is_hidden_on($post_b, 'frontend'));
        $this->assertTrue($service->is_hidden_on($post_c, 'frontend'));
        $this->assertTrue($service->is_hidden_on($post_c, 'api'));
    }

    /**
     * Testing Post resource - comment permission
     *
     * @return void
     */
    public function testPostCommentResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost([ 'post_name' => 'idea-board' ]);

        // Verifying post is still allowed for commenting
        $this->assertTrue(AAM::api()->posts()->is_allowed_to($post_a, 'comment'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:idea-board",
                "Action": "Comment"
            }
        }'));

        // Verifying that post is properly protected
        $this->assertFalse(AAM::api()->posts()->is_allowed_to($post_a, 'comment'));
    }

    /**
     * Testing Post resource - edit permission
     *
     * @return void
     */
    public function testPostEditResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();

        // Verifying post is still allowed for editing
        $this->assertTrue(AAM::api()->posts()->is_allowed_to($post_a, 'edit'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Edit"
            }
        }'));

        // Verifying that post is properly protected
        $this->assertFalse(AAM::api()->posts()->is_allowed_to($post_a, 'edit'));
    }

    /**
     * Testing Post resource - delete permission
     *
     * @return void
     */
    public function testPostDeleteResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();

        // Verifying post is still allowed for deletion
        $this->assertTrue(AAM::api()->posts()->is_allowed_to($post_a, 'delete'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Delete"
            }
        }'));

        // Verifying that post is properly protected
        $this->assertFalse(AAM::api()->posts()->is_allowed_to($post_a, 'delete'));
    }

    /**
     * Testing Post resource - publish permission
     *
     * @return void
     */
    public function testPostPublishResourceInit()
    {
        // Creating a dummy post
        $post_a = $this->createPost();

        // Verifying post is still allowed for publishing
        $this->assertTrue(AAM::api()->posts()->is_allowed_to($post_a, 'publish'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Publish"
            }
        }'));

        // Verifying that post is properly protected
        $this->assertFalse(AAM::api()->posts()->is_allowed_to($post_a, 'publish'));
    }

}