<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for the AAM "Posts" framework service
 */
final class PostsTest extends TestCase
{

    /**
     * Verify that post is hidden
     *
     * This test uses shortcut content method is_hidden
     *
     * @return void
     */
    public function testPostIsHidden()
    {
        $post_a  = get_post($this->createPost());
        $post_b  = $this->createPost();
        $post_c  = $this->createPost([ 'post_name' => 'test-post-c' ]);
        $post_d  = $this->createPost([ 'post_name' => 'test-post-d' ]);
        $service = AAM::api()->posts();

        // Set post permissions
        $this->assertTrue($service->deny($post_a, 'list'));
        $this->assertTrue($service->deny($post_b, 'list'));
        $this->assertTrue($service->deny([
            'slug'      => 'test-post-c',
            'post_type' => 'post'
        ], 'list'));
        $this->assertTrue($service->deny([
            'post_name' => 'test-post-d',
            'post_type' => 'post'
        ], 'list'));

        // Verify that posts are hidden
        $this->assertTrue($service->is_hidden($post_a));
        $this->assertTrue($service->is_hidden($post_b));
        $this->assertTrue($service->is_hidden($post_c));
        $this->assertTrue($service->is_hidden($post_d));
    }

    /**
     * Verify that post is restricted
     *
     * This test uses shortcut content method is_restricted
     *
     * @return void
     */
    public function testPostIsRestricted()
    {
        $post    = $this->createPost();
        $service = AAM::api()->posts();

        // Set post permissions
        $this->assertTrue($service->deny($post, 'read'));

        // Verify that post is restricted to read
        $this->assertTrue($service->is_restricted($post));
    }

    /**
     * Verify that post is restricted due to expiration
     *
     * @return void
     */
    public function testPostAccessExpired()
    {
        $post    = get_post($this->createPost());
        $service = AAM::api()->posts();

        // Set raw post permissions
        $resource = AAM::api()->user()->get_resource('post');
        $this->assertTrue($resource->set_permission($post, 'read', [
            'effect'           => 'deny',
            'restriction_type' => 'expire',
            'expires_after'    => time() - 30
        ]));

        // Verify that post access is expired
        $this->assertTrue($service->is_restricted($post));
        $this->assertTrue($service->is_access_expired($post));
    }

    /**
     * Get list of simple post permissions
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getSimplePostPermissions()
    {
        return [
            [ 'comment' ],
            [ 'edit' ],
            [ 'delete' ],
            [ 'publish' ]
        ];
    }

    /**
     * Test simple post permissions
     *
     * @param string $permission
     *
     * @return void
     */
    #[DataProvider('getSimplePostPermissions')]
    public function testPostPermission($permission)
    {
        $post    = $this->createPost();
        $service = AAM::api()->posts();

        // Set post permissions
        $this->assertTrue($service->deny($post, $permission));

        // Verify post permission
        $this->assertTrue($service->is_denied_to($post, $permission));
        $this->assertFalse($service->is_allowed_to($post, $permission));
    }

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
        $post_b  = $this->createPost();
        $service = AAM::api()->posts();

        // Hide the post B
        $this->assertTrue($service->deny($post_b, 'list'));

        $this->assertTrue($service->is_hidden_on($post_b, 'frontend'));
        $this->assertTrue($service->is_hidden_on($post_b, 'backend'));
        $this->assertTrue($service->is_hidden_on($post_b, 'api'));
        $this->assertTrue($service->is_hidden($post_b));

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(\AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'list' => [
                'effect' => 'deny'
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
        $post_a  = $this->createPost();
        $service = AAM::api()->posts();

        // Hide the post B
        $this->assertTrue($service->hide($post_a, [ 'backend' ]));

        $this->assertFalse($service->is_hidden_on($post_a, 'frontend'));
        $this->assertTrue($service->is_hidden_on($post_a, 'backend'));
        $this->assertFalse($service->is_hidden_on($post_a, 'api'));

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(\AAM_Framework_Service_Settings::DB_OPTION);

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
        $service  = AAM::api()->posts();
        $password = uniqid();

        // Set restrictions
        $this->assertTrue($service->set_password($post_a, $password));

        // Verify that restriction are set correctly
        $this->assertTrue($service->is_password_protected($post_a));
        $this->assertEquals($password, $service->get_password($post_a));

        // Creating a new post and setting password with shortcut method
        $post_b = $this->createPost();

        $this->assertTrue($service->set_password($post_b, $password));

        // Verify that restriction are set correctly
        $this->assertTrue($service->is_password_protected($post_b));
        $this->assertEquals($password, $service->get_password($post_b));

        // Finally create a post with defined password
        $post_c = $this->createPost([ 'post_password' => $password ]);

        // Verify that restriction are set correctly
        $this->assertTrue($service->is_password_protected($post_c));
        $this->assertEquals($password, $service->get_password($post_c));
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
        $service = AAM::api()->posts();
        $expire  = time() + 1;

        // Restrict two posts in two different ways
        $this->assertTrue($service->deny($post_a, 'read'));
        $this->assertTrue($service->set_expiration($post_b, $expire));

        sleep(2); // Pause for 2 seconds

        // Verify that access is restricted to all the posts
        $this->assertTrue($service->is_restricted($post_a));
        $this->assertTrue($service->is_restricted($post_b));
        $this->assertFalse($service->is_access_expired($post_a));
        $this->assertTrue($service->is_access_expired($post_b));
        $this->assertEquals($expire, $service->get_expiration($post_b));
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
        $service  = AAM::api()->posts();
        $redirect = [
            'type' => 'url_redirect',
            'url'  => '/other-location'
        ];

        // Set redirects
        $this->assertTrue($service->set_redirect($post_a, $redirect));

        // Validate permissions
        $this->assertTrue($service->is_redirected($post_a));
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
        $service = AAM::api()->posts();

        // Set permissions
        $this->assertTrue($service->deny($post_a, [
            'read', 'edit', 'comment', 'publish', 'delete'
        ]));

        // Verify that permissions are properly set
        $this->assertTrue($service->is_restricted($post_a));
        $this->assertTrue($service->is_denied_to($post_a, 'edit'));
        $this->assertTrue($service->is_denied_to($post_a, 'comment'));
        $this->assertTrue($service->is_denied_to($post_a, 'publish'));
        $this->assertTrue($service->is_denied_to($post_a, 'delete'));
        $this->assertTrue($service->is_denied_to($post_a, 'read'));
        $this->assertFalse($service->is_allowed_to($post_a, 'read'));
        $this->assertFalse($service->is_allowed_to($post_a, 'comment'));
        $this->assertFalse($service->is_allowed_to($post_a, 'publish'));
        $this->assertFalse($service->is_allowed_to($post_a, 'delete'));
        $this->assertFalse($service->is_allowed_to($post_a, 'edit'));
    }

    /**
     * Testing aggregation for posts
     *
     * @return void
     */
    public function testPostPermissionsAggregation()
    {
        $post_a = $this->createPost();
        $post_b = $this->createPost();

        $this->assertTrue(AAM::api()->posts()->hide($post_a));
        $this->assertTrue(AAM::api()->posts()->hide($post_b, [ 'backend', 'api' ]));

        // Making sure that the aggregator is working as expected
        $this->assertEquals([
            "{$post_a}|post" => [
                'list' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ],
            "{$post_b}|post" => [
                'list' => [
                    'effect'         => 'deny',
                    'on'             => [ 'backend', 'api' ],
                    '__access_level' => 'visitor'
                ]
            ]
        ], AAM::api()->posts()->aggregate());
    }

    /**
     * Making sure that aggregate take access policies Post resource into
     * consideration
     *
     * @return void
     */
    public function testPostAggregationWithPolicy()
    {
        $post_a = $this->createPost();
        $post_b = $this->createPost([ 'post_name' => 'test-post' ]);

        // Create a policy that hides 2 posts
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Post:post:' . $post_a . '",
                    "Action": "List"
                },
                {
                    "Effect": "deny",
                    "Resource": [
                        "Post:post:test-post"
                    ],
                    "Action": "List",
                    "On": [ "backend", "api" ]
                }
            ]
        }'));

        $this->assertEquals([
            "{$post_a}|post" => [
                'list' => [
                    'effect'         => 'deny',
                    'on'             => [ 'frontend', 'backend', 'api' ],
                    '__access_level' => 'visitor'
                ]
            ],
            "{$post_b}|post" => [
                'list' => [
                    'effect'         => 'deny',
                    'on'             => [ 'backend', 'api' ],
                    '__access_level' => 'visitor'
                ]
            ]
        ], AAM::api()->posts()->aggregate());
    }

    /**
     * Making sure that internal post permissions have higher precedence than those
     * that are set with JSON access policy
     *
     * @return void
     */
    public function testCorrectAggregationPermissionsMerge()
    {
        $post_a = $this->createPost();

        // Setting post permission
        $this->assertTrue(AAM::api()->posts()->hide($post_a));

        // Create a policy that hides 2 posts
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "allow",
                    "Resource": "Post:post:' . $post_a . '",
                    "Action": "List"
                }
            ]
        }'));

        $this->assertEquals([
            "{$post_a}|post" => [
                'list' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ]
        ], AAM::api()->posts()->aggregate());
    }

}