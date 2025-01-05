<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Framework API gateway test
 */
final class ApiGatewayTest extends TestCase
{

    /**
     * Testing default framework context
     *
     * Making sure that the default context is updated accordingly and new
     * current user is set as the default access level
     *
     * @return void
     */
    public function testDefaultContext() : void
    {
        $user_id = $this->createUser([ 'role' => 'subadmin' ]);

        // Setting current user
        wp_set_current_user($user_id);

        // Just user any service and do not provide runtime context. This will
        // force the framework to use the default context
        $service = AAM::api()->access_denied_redirect();

        $this->assertEquals($service->access_level->ID, $user_id);
    }

    /**
     * Testing runtime (aka inline) context
     *
     * Making sure that the default context is not taken into consideration when
     * inline context provided
     *
     * @return void
     */
    public function testRuntimeContext() : void
    {
        $user_id_a = $this->createUser([ 'role' => 'subscriber' ]);

        // Setting new current user. The default context will be updated to this
        // user
        wp_set_current_user($user_id_a);

        // Creating new user and use the inline context instead
        $user_id_b = $this->createUser([ 'role' => 'subscriber' ]);

        // Just user any service and do not provide runtime context. This will
        // force the framework to use the default context
        $service = AAM::api()->access_denied_redirect([
            'access_level' => AAM::api()->user($user_id_b)
        ]);

        $this->assertEquals($service->access_level->ID, $user_id_b);
    }

    /**
     * Testing post generator
     *
     * @return void
     */
    // public function testPostGenerator()
    // {
    //     $this->resetTables();

    //     // Creating simple posts
    //     $post_a = $this->createPost();
    //     $post_b = $this->createPost();
    //     $post_c = $this->createPost();

    //     // Querying these posts through Post Type resource
    //     $post_type = AAM::api()->content()->post_type('post');
    //     $posts     = AAM::api()->posts($post_type);
    //     $total     = 0;

    //     $this->assertTrue(is_a($posts, \Generator::class));

    //     // Verifying that we get all 3 posts
    //     foreach($posts as $post) {
    //         $total++;

    //         $this->assertTrue(is_a($post, \AAM_Framework_Resource_Post::class));
    //         $this->assertContains(
    //             $post->get_internal_id(),
    //             [ "$post_a|post", "$post_b|post", "$post_c|post" ]
    //         );
    //     }

    //     $this->assertEquals(3, $total);

    //     // Creating 3 pages where 2 of them are children
    //     $page_a = $this->createPost([ 'post_type' => 'page' ]);
    //     $page_b = $this->createPost([ 'post_type' => 'page', 'post_parent' => $page_a ]);
    //     $page_c = $this->createPost([ 'post_type' => 'page', 'post_parent' => $page_a ]);

    //     // Making sure we can fetch child pages
    //     $page  = AAM::api()->content()->post($page_a);
    //     $pages = AAM::api()->posts($page);
    //     $total = 0;

    //     $this->assertTrue(is_a($pages, \Generator::class));

    //     // Verifying that we get 2 pages
    //     foreach($pages as $child) {
    //         $total++;

    //         $this->assertTrue(is_a($child, \AAM_Framework_Resource_Post::class));
    //         $this->assertContains(
    //             $child->get_internal_id(), [ "$page_b|page", "$page_c|page" ]
    //         );
    //     }

    //     $this->assertEquals(2, $total);

    //     // Creating 2 more posts but this time assigned to a term
    //     $term_a = $this->createTerm();
    //     $post_d = $this->createPost([ 'terms' => [ $term_a ] ]);
    //     $post_e = $this->createPost([ 'terms' => [ $term_a ] ]);
    //     $total  = 0;

    //     // Getting the list of all posts assigned to a term
    //     $posts = AAM::api()->posts(
    //         AAM::api()->content()->term($term_a, 'category', 'post')
    //     );

    //     $this->assertTrue(is_a($posts, \Generator::class));

    //     // Verifying that we get 2 pages
    //     foreach($posts as $child) {
    //         $total++;

    //         $this->assertTrue(is_a($child, \AAM_Framework_Resource_Post::class));
    //         $this->assertContains(
    //             $child->get_internal_id(), [ "$post_d|post", "$post_e|post" ]
    //         );
    //     }

    //     $this->assertEquals(2, $total);
    // }

    /**
     * Test term generators
     *
     * @return void
     */
    // public function testTermGenerator()
    // {
    //     $this->resetTables();

    //     $term_a = $this->createTerm();
    //     $term_b = $this->createTerm();
    //     $total  = 0;

    //     $terms = AAM::api()->terms(AAM::api()->content()->taxonomy('category'));

    //     // Verifying that we obtain all the terms of a given taxonomy
    //     $this->assertTrue(is_a($terms, \Generator::class));

    //     // Verifying that we get 2 terms
    //     foreach($terms as $child) {
    //         $total++;

    //         $this->assertTrue(is_a($child, \AAM_Framework_Resource_Term::class));
    //         $this->assertContains(
    //             $child->term_id, [ $term_a, $term_b ]
    //         );
    //     }

    //     $this->assertEquals(2, $total);

    //     // Verifying that we get all the terms of a given post type
    //     $tag_a = $this->createTerm([ 'taxonomy' => 'post_tag' ]);
    //     $terms = AAM::api()->terms(
    //         AAM::api()->content()->post_type('post')
    //     );

    //     // Verifying that we obtain all the terms of a given taxonomy
    //     $this->assertTrue(is_a($terms, \Generator::class));

    //     // Verifying that we get 3 terms
    //     $total = 0;

    //     foreach($terms as $child) {
    //         $total++;

    //         $this->assertTrue(is_a($child, \AAM_Framework_Resource_Term::class));
    //         $this->assertContains(
    //             $child->term_id, [ $term_a, $term_b, $tag_a ]
    //         );
    //     }

    //     $this->assertEquals(3, $total);

    //     // Verifying that we can pull child terms
    //     $term_c = $this->createTerm([ 'parent' => $term_a ]);
    //     $term_d = $this->createTerm([ 'parent' => $term_a ]);
    //     $total  = 0;

    //     $terms = AAM::api()->terms(AAM::api()->content()->term($term_a));

    //     // Verifying that we obtain all the terms of a given taxonomy
    //     $this->assertTrue(is_a($terms, \Generator::class));

    //     foreach($terms as $child) {
    //         $total++;

    //         $this->assertTrue(is_a($child, \AAM_Framework_Resource_Term::class));
    //         $this->assertContains(
    //             $child->term_id, [ $term_c, $term_d ]
    //         );
    //     }

    //     $this->assertEquals(2, $total);
    // }

}