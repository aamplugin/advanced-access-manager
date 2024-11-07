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
    AAM\UnitTest\Utility\TestCase;

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
        $this->assertEquals($post_id, $post->get_internal_id());

        // Making sure we can get a post by slug
        $post_id = $this->createPost([ 'post_name' => 'get-post-resource' ]);
        $post    = AAM::api()->content()->post('get-post-resource', 'post');

        $this->assertEquals('AAM_Framework_Resource_Post', get_class($post));
        $this->assertEquals('get-post-resource', $post->get_core_instance()->post_name);
        $this->assertEquals($post_id, $post->get_internal_id());
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

}