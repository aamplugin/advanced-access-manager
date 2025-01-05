<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    AAM_Framework_Resource_Post,
    AAM_Framework_Resource_Term,
    AAM\UnitTest\Utility\TestCase;

/**
 * AAM Content service test suite
 */
final class ContentTest extends TestCase
{

    /**
     * Test post password protection
     *
     * @return void
     */
    public function testPostPasswordProtected()
    {
        $post_a  = $this->createPost();
        $service = AAM::api()->posts();

        // Verify that post password is not yet required
        $this->assertFalse(post_password_required($post_a));

        // Set password
        $service->set_password($post_a, uniqid());

        // Verify that post password is required
        $this->assertTrue(post_password_required($post_a));

        // Take the native route
        $password = uniqid();
        $post_b   = $this->createPost([ 'post_password' => $password ]);

        // Verify password
        $this->assertTrue(post_password_required($post_b));
        $this->assertEquals($password, $service->get_password($post_b));
    }

    /**
     * Test post visibility
     *
     * @return void
     */
    public function testPostVisibility()
    {
        $post_a  = $this->createPost();
        $post_b  = $this->createPost();
        $post_c  = $this->createPost();
        $service = AAM::api()->posts();

        // Prepare the quiring args
        $args = [
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ];

        // Verifying that all 3 posts are visible
        $posts = get_posts($args);

        $this->assertContains($post_a, $posts);
        $this->assertContains($post_b, $posts);
        $this->assertContains($post_c, $posts);

        // Hiding Post A & Post C
        $service->hide($post_a);
        $service->hide($post_c);

        // Confirming that these posts are no longer visible
        $posts = get_posts($args);

        $this->assertNotContains($post_a, $posts);
        $this->assertNotContains($post_c, $posts);
        $this->assertContains($post_b, $posts);
    }

    /**
     * Test page visibility
     *
     * @return void
     */
    public function testPageVisibility()
    {
        $page_a  = $this->createPost([ 'post_type' => 'page' ]);
        $page_b  = $this->createPost([ 'post_type' => 'page' ]);
        $page_c  = $this->createPost([ 'post_type' => 'page' ]);
        $service = AAM::api()->posts();

        // Verifying that all 3 pages are visible
        $pages = array_map(function($p) { return $p->ID; }, get_pages());

        $this->assertContains($page_a, $pages);
        $this->assertContains($page_b, $pages);
        $this->assertContains($page_c, $pages);

        // Hiding Page A & Page C
        $service->hide($page_a);
        $service->hide($page_c);

        // Confirming that these pages are no longer visible
        $pages = array_map(function($p) { return $p->ID; }, get_pages());

        $this->assertNotContains($page_a, $pages);
        $this->assertNotContains($page_c, $pages);
        $this->assertContains($page_b, $pages);
    }

    /**
     * Verify that teaser message is correctly set
     *
     * @return void
     */
    public function testPostTeaserMessage()
    {
        $post_a  = $this->createPost([ 'post_content' => 'Test content' ]);
        $service = AAM::api()->posts();

        // Set current post
        $GLOBALS['post'] = $post_a;

        // Verify that post content is original
        $this->assertEquals(
            '<p>Test content</p>', $this->captureOutput('the_content')
        );

        // Set teaser message
        $this->assertTrue(
            $service->set_teaser_message($post_a, 'You are not allowed')
        );

        // Verify that post content is modified
        $this->assertEquals(
            '<p>You are not allowed</p>', $this->captureOutput('the_content')
        );

        // Reset the global variable
        unset($GLOBALS['post']);
    }

    /**
     * Test that commenting ability is denied
     *
     * @return void
     */
    public function testPostCommenting()
    {
        $post_a  = $this->createPost([ 'post_content' => 'Test content' ]);
        $service = AAM::api()->content();

        // Verify that commenting is open for the post
        $this->assertTrue(comments_open($post_a));

        // Set permission
        $this->assertTrue($service->get_post($post_a)->add_permission('comment'));
        $this->assertFalse(comments_open($post_a));
    }

    /**
     * Test simple post permissions for authorized user
     *
     * @return void
     */
    public function testPostSimplePermissions()
    {
        $user_id = $this->createUser([ 'role' => 'editor' ]);
        $post_a  = $this->createPost();

        // Set current user
        wp_set_current_user($user_id);

        // Verify that user is allowed to perform all actions
        $this->assertTrue(current_user_can('edit_post', $post_a));
        $this->assertTrue(current_user_can('publish_post', $post_a));
        $this->assertTrue(current_user_can('delete_post', $post_a));

        // Set new permissions
        $this->assertTrue(AAM::api()->posts()->deny($post_a, [
            'edit', 'publish', 'delete'
        ]));

        // Verify that user is no longer allowed to perform actions
        $this->assertFalse(current_user_can('edit_post', $post_a));
        $this->assertFalse(current_user_can('publish_post', $post_a));
        $this->assertFalse(current_user_can('delete_post', $post_a));
    }

    /**
     * Test that we can obtain Term resource
     *
     * @return void
     */
    public function testGetTerm()
    {
        $term_a  = $this->createTerm([ 'slug' => 'sample-term' ]);
        $service = AAM::api()->content();

        // Test that we can obtain Term resource with just providing ID
        $term = $service->term($term_a);

        $this->assertEquals(AAM_Framework_Resource_Term::class, get_class($term));
        $this->assertEquals($term_a, $term->term_id);
        $this->assertEquals("{$term_a}|category", $term->get_internal_id());

        // Test we can obtain Term resource with slug and taxonomy
        $term = $service->term('sample-term', 'category');

        $this->assertEquals(AAM_Framework_Resource_Term::class, get_class($term));
        $this->assertEquals($term_a, $term->term_id);
        $this->assertEquals("{$term_a}|category", $term->get_internal_id());

        // Test we can obtain Term resource with array and ID
        $term = $service->term([ 'id' => $term_a, 'taxonomy' => 'category' ]);

        $this->assertEquals(AAM_Framework_Resource_Term::class, get_class($term));
        $this->assertEquals($term_a, $term->term_id);
        $this->assertEquals("{$term_a}|category", $term->get_internal_id());

        // Test we can obtain Term resource with array and slug
        $term = $service->term([ 'slug' => 'sample-term', 'taxonomy' => 'category' ]);

        $this->assertEquals(AAM_Framework_Resource_Term::class, get_class($term));
        $this->assertEquals($term_a, $term->term_id);
        $this->assertEquals("{$term_a}|category", $term->get_internal_id());
    }

}