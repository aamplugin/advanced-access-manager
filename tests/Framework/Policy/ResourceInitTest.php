<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test resource initialization with policies
 */
final class ResourceInitTest extends TestCase
{

    /**
     * Testing that we can properly initialize the admin toolbar resource
     *
     * @return void
     */
    public function testToolbarResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(
            AAM::api()->admin_toolbar()->is_restricted('documentation')
        );

        // Creating a new policy & attaching it to current access level
        AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Toolbar:documentation",
                "Effect": "deny"
            }
        }');

        // Verifying that toolbar item is restricted
        $this->assertTrue(
            AAM::api()->admin_toolbar()->is_restricted('documentation')
        );
    }

    /**
     * Testing that we can properly initialize the RESTful API route resource
     *
     * @return void
     */
    public function testRouteResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(
            AAM::api()->api_routes()->is_restricted('/oembed/1.0/proxy')
        );

        // Creating a new policy & attaching it to current access level
        AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Route:/oembed/1.0/proxy:get"
                }
            ]
        }');

        // Verifying that API route is restricted
        $this->assertTrue(
            AAM::api()->api_routes()->is_restricted('/oembed/1.0/proxy')
        );
    }

    /**
     * Testing that we can properly initialize the backend menu resource
     *
     * @return void
     */
    public function testBackendMenuResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(
            AAM::api()->backend_menu()->is_restricted('edit-tags.php?taxonomy=category')
        );

        // Creating a new policy & attaching it to current access level
        AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "BackendMenu:edit-tags.php?taxonomy=category",
                "Effect": "deny"
            }
        }');

        // Verifying that toolbar item is restricted
        $this->assertTrue(
            AAM::api()->backend_menu()->is_restricted('edit-tags.php?taxonomy=category')
        );
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
        AAM::api()->policies()->create('{
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
        }');

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
        AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Resource": "Post:post:' . $post_a . '",
                    "Effect": "deny",
                    "Action": "Read",
                    "Teaser": "Nope!"
                }
            ]
        }');

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
        AAM::api()->policies()->create('{
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
        }');

        AAM::api()->policies()->create('{
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
        }');

        AAM::api()->policies()->create('{
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
        }');

        AAM::api()->policies()->create('{
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
        }');

        AAM::api()->policies()->create('{
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
        }');

        AAM::api()->policies()->create('{
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
        }');

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

}