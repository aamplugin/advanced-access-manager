<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    AAM_Core_Object_Post,
    AAM_Core_Object_Policy,
    AAM_Core_Policy_Factory,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait,
    AAM\AddOn\PlusPackage\Object\Term;

/**
 * Access Policy integration
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyServiceIntegrationTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

    /**
     * Test that Access Policy integrates with Content service
     *
     * Covering the resource "Term:category:37"
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTermIntegration()
    {
        $this->preparePlayground('term');

        $root = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_LEVEL_1_ID . '|category'
        );

        $this->assertFalse($root->isAllowedTo('edit'));

        $level3 = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_LEVEL_3_ID . '|category'
        );

        $this->assertTrue($level3->isAllowedTo('edit'));
    }

    /**
     * Test that Access Policy integrates with Content service through PostType
     * resource
     *
     * Covering the resource "PostType:post:posts"
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPostTypePostsIntegration()
    {
        $this->preparePlayground('posttype-posts');

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_LEVEL_1_POST_ID
        );

        $this->assertFalse($post->isAllowedTo('edit'));
        $this->assertFalse($post->isAllowedTo('delete'));

        $allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        $this->assertTrue($allowed_post->isAllowedTo('edit'));
        $this->assertTrue($allowed_post->isAllowedTo('delete'));
    }

    /**
     * Test that Access Policy integrates with Content service
     *
     * Covering following resources:
     * - Term:category:37:posts
     * - Term:category:38:post:posts
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTermPostsIntegration()
    {
        $this->preparePlayground('term-posts');

        $post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );
        $this->assertFalse($post->isAllowedTo('delete'));

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => 100,
            'cache_results'    => false,
            'suppress_filters' => false
        ));

        // Confirm that post are hidden
        $this->assertFalse(in_array(AAM_UNITTEST_POST_ID, $posts));
        $this->assertFalse(in_array(AAM_UNITTEST_LEVEL_2_POST_ID, $posts));

        $sub_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_LEVEL_1_POST_ID
        );
        $this->assertTrue($sub_post->isAllowedTo('delete'));

        // Confirm that post is not hidden
        $this->assertTrue(in_array(AAM_UNITTEST_LEVEL_1_POST_ID, $posts));
    }

    /**
     * Test that Access Policy integrates with Content service through Taxonomy
     * resource
     *
     * Covering the resource "Taxonomy:category:terms"
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTaxonomyIntegration()
    {
        $this->preparePlayground('taxonomy');

        $root = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_LEVEL_1_ID . '|category'
        );

        $this->assertFalse($root->isAllowedTo('edit'));
        $this->assertFalse($root->isAllowedTo('delete'));

        $level3 = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_LEVEL_3_ID . '|category'
        );

        $this->assertTrue($level3->isAllowedTo('edit'));
        $this->assertFalse($level3->isAllowedTo('delete'));
    }

    /**
     * Prepare the environment
     *
     * Update Unit Test access policy with proper policy
     *
     * @param string $policy_file
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function preparePlayground($policy_file)
    {
        global $wpdb;

        // Update existing Access Policy with new policy
        $wpdb->update($wpdb->posts, array('post_content' => file_get_contents(
            __DIR__ . '/policies/' . $policy_file . '.json'
        )), array('ID' => AAM_UNITTEST_ACCESS_POLICY_ID));

        //AAM_Core_AccessSettings::getInstance()->set('user.1.policy.291', true);

        $object = AAM::getUser()->getObject(AAM_Core_Object_Policy::OBJECT_TYPE);
        $this->assertTrue(
            $object->updateOptionItem(AAM_UNITTEST_ACCESS_POLICY_ID, true)->save()
        );

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
    }

    /**
     * Test multi-level category hierarchy
     *
     * Assuming that we have Level 1/Level 2/Level 3 categories and settings are
     * propagated corrected down the chain and hide all the posts
     *
     * @return void
     *
     * @access public
     * @version 6.3.0
     */
    public function testMultilevelCategoryIntegration()
    {
        $this->preparePlayground('multilevel-term-posts');

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => 100,
            'cache_results'    => false,
            'suppress_filters' => false
        ));

        $this->assertFalse(in_array(AAM_UNITTEST_LEVEL_1_POST_ID, $posts));
        $this->assertFalse(in_array(AAM_UNITTEST_LEVEL_2_POST_ID, $posts));
    }

}