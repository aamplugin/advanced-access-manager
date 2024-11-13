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
        ], $options['visitor']['post'][$post_b]);

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
        ], $options['visitor']['post'][$post_a]);
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

}