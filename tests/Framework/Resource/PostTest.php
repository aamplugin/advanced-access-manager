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
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_Settings;

/**
 * Test class for the AAM "Post" framework resource
 */
final class PostTest extends TestCase
{

    /**
     * Test that defined LIST permission is properly managed by framework
     *
     * @return void
     *
     * @access public
     */
    public function testHiddenOnAllAreasPost()
    {
        // Let's create few posts and hide one of them
        $post_b = $this->createPost();

        $service = AAM::api()->content();

        // Hide the post B
        $this->assertTrue($service->post($post_b)->add_permission('list', 'deny'));

        // Verifying that post B is hidden on all areas
        $post = $service->post($post_b);

        $this->assertTrue($post->is_hidden_on('frontend'));
        $this->assertTrue($post->is_hidden_on('backend'));
        $this->assertTrue($post->is_hidden_on('api'));
        $this->assertTrue($post->is_hidden());

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'list' => [
                'effect' => 'deny',
                'on' => [
                    'frontend',
                    'backend',
                    'api'
                ]
            ]
        ], $options['visitor']['post'][$post_b . '|post']);

    }

    /**
     * Test that defined LIST permission is properly managed by framework for
     * selected areas
     *
     * @return void
     *
     * @access public
     */
    public function testHiddenOnSelectedAreasPost()
    {
        // Let's create few posts and hide one of them
        $post_a = $this->createPost();

        $service = AAM::api()->content();

        // Hide the post B
        $this->assertTrue($service->post($post_a)->add_permission('list', [
            'effect' => 'deny',
            'on'     => [ 'backend' ]
        ]));

        // Verifying that post B is hidden on all areas
        $post = $service->post($post_a);

        $this->assertFalse($post->is_hidden_on('frontend'));
        $this->assertTrue($post->is_hidden_on('backend'));
        $this->assertFalse($post->is_hidden_on('api'));

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'list' => [
                'effect' => 'deny',
                'on' => [
                    'backend',
                ]
            ]
        ], $options['visitor']['post'][$post_a . '|post']);
    }

    /**
     * Test password protected restriction
     *
     * This method tests three different ways to set password
     *
     * @return void
     */
    public function testPostIsPasswordProtected()
    {
        // Let's create a post and make it password protected
        $post_a   = $this->createPost();
        $service  = AAM::api()->content();
        $password = uniqid();

        // Set restrictions
        $this->assertTrue($service->post($post_a)->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'password_protected',
            'password'         => $password
        ]));

        // Verify that restriction are set correctly
        $this->assertTrue($service->post($post_a)->is_password_protected());
        $this->assertEquals($password, $service->post($post_a)->get_password());

        // Creating a new post and setting password with shortcut method
        $post_b = $this->createPost();

        $this->assertTrue($service->post($post_b)->set_password($password));

        // Verify that restriction are set correctly
        $this->assertTrue($service->post($post_b)->is_password_protected());
        $this->assertEquals($password, $service->post($post_b)->get_password());

        // Finally create a post with defined password
        $post_c = $this->createPost([ 'post_password' => $password ]);

        // Verify that restriction are set correctly
        $this->assertTrue($service->post($post_c)->is_password_protected());
        $this->assertEquals($password, $service->post($post_c)->get_password());
    }

    /**
     * Test post read permission
     *
     * This method will verify that post can be restricted three different ways:
     *  - simple: deny read permission;
     *  - expire: deny read permission after expiration date
     *  - expire shortcut: the same as expire but using set_expiration method
     *
     * @return void
     */
    public function testPostIsRestriction()
    {
        $post_a  = $this->createPost();
        $post_b  = $this->createPost();
        $post_c  = $this->createPost();
        $service = AAM::api()->content();
        $expire  = time() + 1;

        // Restrict two posts in two different ways
        $this->assertTrue($service->post($post_a)->add_permission('read'));
        $this->assertTrue($service->post($post_c)->set_expiration($expire));
        $this->assertTrue($service->post($post_b)->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'expire',
            'expires_after'    => $expire
        ]));

        sleep(3); // Pause for 3 seconds

        // Verify that access is restricted to all the posts
        $this->assertTrue($service->post($post_a)->is_restricted());
        $this->assertTrue($service->post($post_b)->is_restricted());
        $this->assertTrue($service->post($post_c)->is_restricted());
        $this->assertNull($service->post($post_a)->is_expiration_set());
        $this->assertTrue($service->post($post_b)->is_expiration_set());
        $this->assertTrue($service->post($post_c)->is_expiration_set());
        $this->assertEquals($expire, $service->post($post_b)->get_expiration());
        $this->assertEquals($expire, $service->post($post_c)->get_expiration());
    }

    /**
     * Test post redirect
     *
     * This method tests two different ways to set redirect
     *
     * @return void
     */
    public function testPostIsRedirected()
    {
        $post_a   = $this->createPost();
        $post_b   = $this->createPost();
        $service  = AAM::api()->content();
        $redirect = [
            'type' => 'url_redirect',
            'url'  => '/other-location'
        ];

        // Set redirects
        $this->assertTrue($service->post($post_a)->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'redirect',
            'redirect'         => $redirect
        ]));
        $this->assertTrue($service->post($post_b)->set_redirect($redirect));

        // Validate permissions
        $this->assertNull($service->post($post_a)->is_restricted());
        $this->assertTrue($service->post($post_a)->is_redirected());
        $this->assertTrue($service->post($post_b)->is_redirected());
    }

    /**
     * Test simple post permissions
     *
     * @return void
     *
     * @access public
     */
    public function testPostSimplePermissions()
    {
        $post_a  = $this->createPost();
        $service = AAM::api()->content();

        // Set permissions
        $this->assertTrue($service->post($post_a)->add_permissions([
            'read', 'edit', 'comment', 'publish', 'delete'
        ]));

        // Verify that permissions are properly set
        $this->assertTrue($service->post($post_a)->is_restricted());
        $this->assertTrue($service->post($post_a)->is_denied_to('edit'));
        $this->assertTrue($service->post($post_a)->is_denied_to('comment'));
        $this->assertTrue($service->post($post_a)->is_denied_to('publish'));
        $this->assertTrue($service->post($post_a)->is_denied_to('delete'));
        $this->assertTrue($service->post($post_a)->is_denied_to('read'));
        $this->assertFalse($service->post($post_a)->is_allowed_to('read'));
        $this->assertFalse($service->post($post_a)->is_allowed_to('comment'));
        $this->assertFalse($service->post($post_a)->is_allowed_to('publish'));
        $this->assertFalse($service->post($post_a)->is_allowed_to('delete'));
        $this->assertFalse($service->post($post_a)->is_allowed_to('edit'));
    }

    /**
     * Test that permission can be added in various ways
     *
     * @return void
     */
    public function testPostAddPermission()
    {
        $post_a  = $this->createPost();
        $post_b  = $this->createPost();
        $service = AAM::api()->content();

        // Add permissions to a post in several different ways
        $post = $service->post($post_a);
        $this->assertTrue($post->add_permission('edit'));
        $this->assertTrue($post->add_permission('publish', 'deny'));
        $this->assertTrue($post->add_permission('delete', 1));

        // In all 3 ways the permissions should be denied
        $this->assertEquals([
            'edit' => [
                'effect' => 'deny'
            ],
            'publish' => [
                'effect' => 'deny'
            ],
            'delete' => [
                'effect' => 'deny'
            ]
        ], $post->get_permissions());

        // Add permission with fully defined settings
        $post = $service->post($post_b);
        $this->assertTrue($post->add_permission('comment', [
            'effect' => 'allow'
        ]));

        // Validate added permission
        $this->assertEquals([
            'comment' => [
                'effect' => 'allow'
            ]
        ], $post->get_permissions());
    }

    /**
     * Test that permissions can be added in various ways
     *
     * @return void
     */
    public function testPostAddPermissions()
    {
        $post_a  = $this->createPost();
        $post_b  = $this->createPost();
        $service = AAM::api()->content();

        // Add permissions to a post in several different ways
        $post = $service->post($post_a);
        $this->assertTrue($post->add_permissions([ 'edit', 'publish' ]));

        // Verify saved permissions
        $this->assertEquals([
            'edit' => [
                'effect' => 'deny'
            ],
            'publish' => [
                'effect' => 'deny'
            ]
        ], $post->get_permissions());

        // Add permission with fully defined settings
        $post = $service->post($post_b);
        $this->assertTrue($post->add_permissions([
            'edit'    => 'deny',
            'comment' => 'allow'
        ]));

        // Validate added permission
        $this->assertEquals([
            'edit' => [
                'effect' => 'deny'
            ],
            'comment' => [
                'effect' => 'allow'
            ]
        ], $post->get_permissions());
    }

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
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_password_protected()
        );
        $this->assertNull(
            AAM::api()->content()->post($post_b)->is_password_protected()
        );

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
        $service = AAM::api()->content();

        // Verifying that posts are properly protected
        $this->assertTrue($service->post($post_a)->is_password_protected());
        $this->assertEquals('pwd1', $service->post($post_a)->get_password());
        $this->assertTrue($service->post($post_b)->is_password_protected());
        $this->assertEquals('pwd2', $service->post($post_b)->get_password());
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

        // Verifying that both posts are still allowed
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_password_protected()
        );

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
        $service = AAM::api()->content();

        // Verifying that posts are properly protected
        $this->assertTrue($service->post($post_a)->is_teased());
        $this->assertEquals('Nope!', $service->post($post_a)->get_teaser_message());
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
        $this->assertNull(AAM::api()->content()->post($post_a)->is_redirected());
        $this->assertNull(AAM::api()->content()->post($post_b)->is_redirected());
        $this->assertNull(AAM::api()->content()->post($post_c)->is_redirected());
        $this->assertNull(AAM::api()->content()->post($post_d)->is_redirected());
        $this->assertNull(AAM::api()->content()->post($post_e)->is_redirected());
        $this->assertNull(AAM::api()->content()->post($post_f)->is_redirected());

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
        $service = AAM::api()->content();

        // Verifying that posts are properly protected
        $this->assertTrue($service->post($post_a)->is_redirected());
        $this->assertTrue($service->post($post_b)->is_redirected());
        $this->assertTrue($service->post($post_c)->is_redirected());
        $this->assertTrue($service->post($post_d)->is_redirected());
        $this->assertTrue($service->post($post_e)->is_redirected());
        $this->assertTrue($service->post($post_f)->is_redirected());

        $this->assertEquals([
            'type'               => 'page_redirect',
            'redirect_page_slug' => 'authentication-required',
            'http_status_code'   => 301
        ], $service->post($post_a)->get_redirect());

        $this->assertEquals([
            'type'             => 'page_redirect',
            'redirect_page_id' => 76,
            'http_status_code' => 307
        ], $service->post($post_b)->get_redirect());

        $this->assertEquals([
            'type'             => 'url_redirect',
            'redirect_url'     => '/different-page',
            'http_status_code' => 302
        ], $service->post($post_c)->get_redirect());

        $this->assertEquals([
            'type'    => 'custom_message',
            'message' => 'You are not allowed to be here'
        ], $service->post($post_d)->get_redirect());

        $this->assertEquals([
            'type' => 'login_redirect',
        ], $service->post($post_e)->get_redirect());

        $this->assertEquals([
            'type'     => 'trigger_callback',
            'callback' => 'do_redirect_or_return_redirect_url_func',
        ], $service->post($post_f)->get_redirect());
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
        $this->assertNull(AAM::api()->content()->post($post_a)->is_hidden());
        $this->assertNull(AAM::api()->content()->post($post_b)->is_hidden());
        $this->assertNull(AAM::api()->content()->post($post_c)->is_hidden());

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
        $service = AAM::api()->content();

        // Verifying that posts are properly protected
        $this->assertTrue($service->post($post_a)->is_hidden());
        $this->assertTrue($service->post($post_a)->is_hidden_on('frontend'));
        $this->assertTrue($service->post($post_a)->is_hidden_on('backend'));
        $this->assertTrue($service->post($post_a)->is_hidden_on('api'));
        $this->assertTrue($service->post($post_b)->is_hidden_on('frontend'));
        $this->assertTrue($service->post($post_c)->is_hidden_on('frontend'));
        $this->assertTrue($service->post($post_c)->is_hidden_on('api'));
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
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_allowed_to('comment')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:idea-board",
                "Action": "Comment"
            }
        }'));

        // Get the content service
        $service = AAM::api()->content();

        // Verifying that post is properly protected
        $this->assertFalse($service->post($post_a)->is_allowed_to('comment'));
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

        // Verifying post is still allowed for commenting
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_allowed_to('edit')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Edit"
            }
        }'));

        // Get the content service
        $service = AAM::api()->content();

        // Verifying that post is properly protected
        $this->assertFalse($service->post($post_a)->is_allowed_to('edit'));
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

        // Verifying post is still allowed for commenting
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_allowed_to('delete')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Delete"
            }
        }'));

        // Get the content service
        $service = AAM::api()->content();

        // Verifying that post is properly protected
        $this->assertFalse($service->post($post_a)->is_allowed_to('delete'));
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

        // Verifying post is still allowed for commenting
        $this->assertNull(
            AAM::api()->content()->post($post_a)->is_allowed_to('publish')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Post:post:' . $post_a . '",
                "Action": "Publish"
            }
        }'));

        // Get the content service
        $service = AAM::api()->content();

        // Verifying that post is properly protected
        $this->assertFalse($service->post($post_a)->is_allowed_to('publish'));
    }

}