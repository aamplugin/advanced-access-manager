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
 * Test class for the AAM "Content" framework service
 */
final class ContentTest extends TestCase
{

    /**
     * Get post type resource
     *
     * @return void
     */
    public function testGetPostTypeResource() : void
    {
        $post_type = AAM::api()->content()->post_type('page');

        $this->assertEquals('AAM_Framework_Resource_PostType', get_class($post_type));
        $this->assertEquals('page', $post_type->get_core_instance()->name);
        $this->assertEquals('page', $post_type->get_internal_id());
    }

    /**
     * Get taxonomy resource
     *
     * @return void
     */
    public function testGetTaxonomyResource() : void
    {
        $taxonomy = AAM::api()->content()->taxonomy('post_tag');

        $this->assertEquals('AAM_Framework_Resource_Taxonomy', get_class($taxonomy));
        $this->assertEquals('post_tag', $taxonomy->get_core_instance()->name);
        $this->assertEquals('post_tag', $taxonomy->get_internal_id());
    }

    /**
     * Get post resource
     *
     * @return void
     */
    public function testGetPostResource() : void
    {
        $post_id = $this->createPost();
        $post    = AAM::api()->content()->post($post_id);

        $this->assertEquals('AAM_Framework_Resource_Post', get_class($post));
        $this->assertEquals('post', $post->get_core_instance()->post_type);
        $this->assertEquals([
            'id'        => $post_id,
            'post_type' => 'post'
        ], $post->get_internal_id(false));

        // Making sure we can get a post by slug
        $post_id = $this->createPost([ 'post_name' => 'get-post-resource' ]);
        $post    = AAM::api()->content()->post('get-post-resource', 'post');

        $this->assertEquals('AAM_Framework_Resource_Post', get_class($post));
        $this->assertEquals('get-post-resource', $post->get_core_instance()->post_name);
        $this->assertEquals("$post_id|post", $post->get_internal_id());
    }

    /**
     * Get term resource
     *
     * @return void
     */
    public function testGetTermResource() : void
    {
        $term_id = $this->createTerm();
        $term    = AAM::api()->content()->term($term_id);

        $this->assertEquals('AAM_Framework_Resource_Term', get_class($term));
        $this->assertEquals('category', $term->get_core_instance()->taxonomy);
        $this->assertEquals($term_id, $term->get_internal_id());

        // Try to get term with compound key
        $term_id = $this->createTerm([ 'name' => 'Another Category' ]);
        $term    = AAM::api()->content()->term([
            'id'       => $term_id,
            'taxonomy' => 'category'
        ]);

        $this->assertEquals('AAM_Framework_Resource_Term', get_class($term));
        $this->assertEquals('Another Category', $term->get_core_instance()->name);
        $this->assertEquals($term_id . '|category' , $term->get_internal_id(true));
    }

    /**
     * Verify that post is hidden
     *
     * This test uses shortcut content method is_hidden
     *
     * @return void
     */
    public function testPostIsHidden()
    {
        $post    = get_post($this->createPost());
        $service = AAM::api()->content();

        // Set post permissions
        $this->assertTrue($service->post($post->ID)->add_permission('list'));

        // Verify that post is hidden
        $this->assertTrue($service->is_hidden($post));
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
        $post    = get_post($this->createPost());
        $service = AAM::api()->content();

        // Set post permissions
        $this->assertTrue($service->post($post->ID)->add_permission('read'));

        // Verify that post is hidden
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
        $service = AAM::api()->content();

        // Set post permissions
        $this->assertTrue($service->post($post->ID)->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'expire',
            'expires_after'    => time() - 30
        ]));

        // Verify that post is hidden
        $this->assertTrue($service->is_restricted($post));
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
        $post    = get_post($this->createPost());
        $service = AAM::api()->content();

        // Set post permissions
        $this->assertTrue($service->post($post->ID)->add_permission($permission));

        // Verify post permission
        $this->assertTrue($service->is_denied_to($post, $permission));
        $this->assertFalse($service->is_allowed_to($post, $permission));
    }

}