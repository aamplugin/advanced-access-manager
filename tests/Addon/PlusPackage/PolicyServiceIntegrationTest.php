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
    AAM_Core_Policy_Factory,
    PHPUnit\Framework\TestCase,
    AAM_Framework_Type_Resource,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\AddOn\PlusPackage\Object\Term;

/**
 * Access Policy integration
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyServiceIntegrationTest extends TestCase
{
    use ResetTrait;

    protected static $policy_id;
    protected static $post_id;
    protected static $post_b_id;
    protected static $post_c_id;
    protected static $top_term_id;
    protected static $sub_term_id;
    protected static $sub_sub_term_id;
    protected static $tag_a;
    protected static $tag_b;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        // Setup a default policy placeholder
        self::$policy_id = wp_insert_post(array(
            'post_title'  => 'Unittest Policy Placeholder',
            'post_status' => 'publish',
            'post_type'   => 'aam_policy'
        ));

        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Sample Post',
            'post_name'   => 'plus-package-post',
            'post_status' => 'publish'
        ));

        self::$post_b_id = wp_insert_post(array(
            'post_title'  => 'Sample Post B',
            'post_name'   => 'plus-package-post-b',
            'post_status' => 'publish'
        ));

        self::$post_c_id = wp_insert_post(array(
            'post_title'  => 'Sample Post C',
            'post_name'   => 'plus-package-post-c',
            'post_status' => 'publish'
        ));

        self::$top_term_id = wp_insert_term('Category', 'category', array(
            'slug' => 'plus-package-category'
        ))['term_id'];
        wp_set_post_terms(self::$post_id, self::$top_term_id, 'category');
        wp_set_post_terms(self::$post_b_id, self::$top_term_id, 'category');

        self::$sub_term_id = wp_insert_term('Sub Category', 'category', array(
            'parent' => self::$top_term_id,
            'slug' => 'plus-package-sub-category'
        ))['term_id'];
        wp_set_post_terms(self::$post_c_id, self::$sub_term_id, 'category');

        self::$sub_sub_term_id = wp_insert_term('Sub Sub Category', 'category', array(
            'parent' => self::$sub_term_id,
            'slug' => 'plus-package-sub-sub-category'
        ))['term_id'];

        self::$tag_a = wp_insert_term('Tab A', 'post_tag', array(
            'slug' => 'tag-a'
        ))['term_id'];

        self::$tag_b = wp_insert_term('Tab B', 'post_tag', array(
            'slug' => 'tag-b'
        ))['term_id'];
    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

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
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        $this->assertFalse($root->isAllowedTo('edit'));

        $level3 = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, self::$sub_sub_term_id . '|category'
        );

        $this->assertTrue($level3->isAllowedTo('edit'));
    }

    /**
     * Test that visibility settings are inherited
     *
     * @return void
     *
     * @access public
     * @version 6.5.0
     */
    public function testTermVisibilityIntegration()
    {
        $this->preparePlayground('term-visibility-inheritance');

        $ids = get_terms(array(
            'taxonomy'   => 'category',
            'fields'     => 'ids',
            'hide_empty' => false
        ));

        $this->assertContains(intval(self::$sub_term_id), $ids);
        $this->assertContains(intval(self::$sub_sub_term_id), $ids);
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
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_b_id
        );

        $this->assertFalse($post->isAllowedTo('edit'));
        $this->assertFalse($post->isAllowedTo('delete'));

        $allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
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
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_b_id
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
        $this->assertFalse(in_array(self::$post_b_id, $posts));
        $this->assertFalse(in_array(self::$post_c_id, $posts));

        $sub_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );
        $this->assertTrue($sub_post->isAllowedTo('delete'));

        // Confirm that post is not hidden
        $this->assertTrue(in_array(self::$post_id, $posts));
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
            Term::OBJECT_TYPE, self::$top_term_id . '|category'
        );

        $this->assertFalse($root->isAllowedTo('edit'));
        $this->assertFalse($root->isAllowedTo('delete'));

        $level3 = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, self::$sub_sub_term_id . '|category'
        );

        $this->assertTrue($level3->isAllowedTo('edit'));
        $this->assertFalse($level3->isAllowedTo('delete'));
    }

    /**
     * Test that Access Policy integrates with Content service where a default
     * category is defined as integer
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testDefaultSingleIntCategory()
    {
        $this->preparePlayground(sprintf('{
            "Param": [
                {
                    "Key": "post:default:category",
                    "Value": %d
                }
            ]
        }', self::$top_term_id), false);

        $this->assertEquals(self::$top_term_id, get_option('default_category'));
    }

    /**
     * Test that Access Policy integrates with Content service where a default
     * category is defined as slug
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testDefaultSingleSlugCategory()
    {
        $this->preparePlayground('default-term-single-slug');

        $this->assertEquals(self::$top_term_id, get_option('default_category'));
    }

    /**
     * Test that Access Policy integrates with Content service where a default
     * category is defined as slug
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testDefaultMultipleTagsMixed()
    {
        $this->preparePlayground(sprintf('{
            "Param": [
                {
                    "Key": "post:default:post_tag",
                    "Value": [%d, "tag-b"]
                }
            ]
        }', self::$tag_a), false);

        $id = wp_insert_post(array(
            'post_title'  => 'Unit Test Automation',
            'post_type'   => 'post',
            'post_status' => 'draft'
        ));

        $this->assertTrue(is_int($id));

        $new_terms = wp_get_object_terms($id, 'post_tag', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(self::$tag_a), $new_terms);
        $this->assertContains(intval(self::$tag_b), $new_terms);

        wp_delete_post($id, true);
    }

    /**
     * Test that Access Policy integrates with Content service where a default
     * category is defined as slugs and coming from user_meta option
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testDefaultMultipleTagsFromUserMeta()
    {
        $this->preparePlayground('default-term-multi-meta');

        add_user_meta(
            AAM_UNITTEST_ADMIN_USER_ID, 'default_tags', array('tag-a', 'tag-b'), true
        );

        $id = wp_insert_post(array(
            'post_title'  => 'Unit Test Automation',
            'post_type'   => 'post',
            'post_status' => 'draft'
        ));

        $this->assertTrue(is_int($id));

        $new_terms = wp_get_object_terms($id, 'post_tag', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(self::$tag_a), $new_terms);
        $this->assertContains(intval(self::$tag_b), $new_terms);

        wp_delete_post($id, true);
        delete_user_meta(AAM_UNITTEST_ADMIN_USER_ID, 'default_tags');
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

        $this->assertFalse(in_array(self::$post_id, $posts));
        $this->assertFalse(in_array(self::$post_b_id, $posts));
    }

    /**
     * Test that access to edit or delete is allowed only for posts in one term
     *
     * @return void
     *
     * @access public
     * @version 5.4.2
     */
    public function testAllowedPostsInWhitelistedCategory()
    {
        $this->preparePlayground('default-posts-allowed-term');

        $allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_c_id
        );

        $this->assertTrue($allowed_post->isAllowedTo('edit'));
        $this->assertTrue($allowed_post->isAllowedTo('delete'));

        $not_allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertFalse($not_allowed_post->isAllowedTo('edit'));
        $this->assertFalse($not_allowed_post->isAllowedTo('delete'));
    }

    /**
     * Test that access to edit or delete denied with multi-term setup even when
     * one term is allowed
     *
     * @return void
     *
     * @access public
     * @version 5.4.2
     */
    public function testDeniedPostsInWhitelistedMultiTermCategory()
    {
        $this->preparePlayground('default-posts-allowed-term');

        // Add tag to testing post
        wp_add_post_tags(self::$post_id, self::$tag_a);

        $allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertFalse($allowed_post->isAllowedTo('edit'));
        $this->assertFalse($allowed_post->isAllowedTo('delete'));

        // Reset to default
        wp_remove_object_terms(self::$post_id, self::$tag_a, 'post_tag');
    }

    /**
     * Test that access to edit or delete denied with multi-term setup even when
     * one term is allowed
     *
     * @return void
     *
     * @access public
     * @version 5.4.2
     */
    public function testAllowPostsInWhitelistedMultiTermCategory()
    {
        $this->preparePlayground('default-posts-allowed-multi-term');

        // Add tag to testing post
        wp_add_post_tags(self::$post_id, self::$tag_a);

        $allowed_post = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertTrue($allowed_post->isAllowedTo('edit'));
        $this->assertTrue($allowed_post->isAllowedTo('delete'));

        // Reset to default
        wp_remove_object_terms(self::$post_id, self::$tag_a, 'post_tag');
    }

    /**
     * Test if Role:* works as expected
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testRoleListWildcard()
    {
        $roles = get_editable_roles();

        $this->assertTrue(count($roles) > 0);

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "Role:*",
                "Action": "List"
            }
        }', false);

        $roles = get_editable_roles();

        $this->assertTrue(count($roles) === 0);
    }

    /**
     * Test if User:* works as expected
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function testUserListWildcard()
    {
        $users = get_users(array(
            'fields' => 'ID'
        ));

        $this->assertTrue(count($users) > 0);

        $this->preparePlayground('{
            "Statement": {
                "Effect": "deny",
                "Resource": "User:*",
                "Action": "List"
            }
        }', false);

        $users = get_users(array(
            'fields' => 'ID'
        ));

        $this->assertTrue(count($users) === 0);
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
    protected function preparePlayground($policy, $is_file = true)
    {
        global $wpdb;

        if ($is_file) {
            $content = file_get_contents(
                __DIR__ . '/policies/' . $policy . '.json'
            );
        } else {
            $content = $policy;
        }

        // Update existing Access Policy with new policy
        $wpdb->update(
            $wpdb->posts,
            array('post_content' => $content),
            array('ID' => self::$policy_id)
        );

        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::ACCESS_POLICY
        );

        $this->assertTrue(
            $resource->set_explicit_setting(self::$policy_id, true)
        );

        // Reset Access Policy Factory cache
        AAM_Core_Policy_Factory::reset();
        $this->_resetSubjects();
    }

}