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
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\AddOn\PlusPackage\Object\Term,
    AAM\AddOn\PlusPackage\Object\Type,
    AAM\AddOn\PlusPackage\Hooks\ContentHooks;

/**
 * Test cases for the Plus Package content visibility management
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class ContentVisibilityTest extends TestCase
{
    use ResetTrait;

    protected static $term_id;
    protected static $post_id;
    protected static $page_id;
    protected static $sub_page_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        $term          = wp_insert_term('Uncategorized', 'category');
        self::$term_id = $term['term_id'];
        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Plus Package',
            'post_name'   => 'plus-package',
            'post_status' => 'publish'
        ));
        wp_set_post_terms(self::$post_id, self::$term_id, 'category');

        self::$page_id = wp_insert_post(array(
            'post_title'  => 'Plus Package Page',
            'post_name'   => 'plus-package-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));

        self::$sub_page_id = wp_insert_post(array(
            'post_title'  => 'Sub Plus Package Page',
            'post_name'   => 'sub-plus-package-page',
            'post_type'   => 'page',
            'post_parent' => self::$page_id,
            'post_status' => 'publish'
        ));
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
     * Test that page is hidden when parent page is hidden to
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromParentPost()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$page_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $posts = get_posts(array(
            'post_type'        => 'page',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertFalse(in_array(self::$sub_page_id, $posts));
    }

    /**
     * Test that post is hidden when parent term states so
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromParentTerm()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertFalse(in_array(self::$post_id, $posts));
    }

    /**
     * Test that posts are hidden when the entire post type states so
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromParentType()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Type::OBJECT_TYPE, 'post'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertCount(0, $posts);
    }

    /**
     * Test that post if visible if explicitly defined so
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromParentTermButOverwritten()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        // Check if save returns positive result
        $this->assertTrue($post->updateOptionItem('hidden', false)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertContains(self::$post_id, $posts);
    }

    /**
     * Test that post if visible if explicitly defined so
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritanceFromParentTypeButOverwritten()
    {
        $user   = AAM::getUser();
        $type = $user->getObject(Type::OBJECT_TYPE, 'post');

        // Check if save returns positive result
        $this->assertTrue($type->updateOptionItem('post/hidden', true)->save());

        $term = $user->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($term->updateOptionItem('post/hidden', false)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertContains(self::$post_id, $posts);
    }

    /**
     * Covering the scenario with conflicting term access
     *
     * @return void
     * @link https://github.com/aamplugin/advanced-access-manager/issues/277
     */
    public function testTermDoubleRestrictionConflict()
    {
        // Setting up the necessary terms and posts
        $category_a = wp_insert_term('Category A', 'category')['term_id'];
        $category_b = wp_insert_term('Category B', 'category')['term_id'];
        $post_a     = wp_insert_post(array(
            'post_title'  => 'Post A',
            'post_name'   => 'post-a',
            'post_status' => 'publish'
        ));
        wp_set_post_terms($post_a, $category_a, 'category');
        $post_b     = wp_insert_post(array(
            'post_title'  => 'Post B',
            'post_name'   => 'post-b',
            'post_status' => 'publish'
        ));
        wp_set_post_terms($post_b, [$category_b, $category_a], 'category');

        // Enabling multi-role & term merging preference to true
        \AAM_Core_Config::set('core.settings.term.merge.preference', 'allow');

        $default = AAM::api()->getDefault();

        // Setting default access controls to both Category A & B
        $term = $default->getObject(Term::OBJECT_TYPE, $category_a . '|category');
        $this->assertTrue($term->updateOptionItem('post/hidden', true)->save());

        $term = $default->getObject(Term::OBJECT_TYPE, $category_b . '|category');
        $this->assertTrue($term->updateOptionItem('post/hidden', true)->save());

        // For the Administrator role, allow Category A
        $role = AAM::api()->getRole('administrator');

        $term = $role->getObject(Term::OBJECT_TYPE, $category_a . '|category');
        $this->assertTrue($term->updateOptionItem('post/hidden', false)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();
        \AAM_Core_Config::bootstrap();

        $posts = get_posts(array(
            'post_type'        => 'post',
            'fields'           => 'ids',
            'numberposts'      => -1,
            'suppress_filters' => false
        ));

        $this->assertContains($post_a, $posts);
        $this->assertContains($post_b, $posts);
    }

}