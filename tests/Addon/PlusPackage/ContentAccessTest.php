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
 * Test cases for the Plus Package content access management
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class ContentAccessTest extends TestCase
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
     * Test that access settings are inherited from the parent term
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritPostAccessFromParentTerm()
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

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertTrue($post->is('hidden'));
    }

    /**
     * Test that access settings are inherited from the parent post type
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritPostAccessFromParentType()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(Type::OBJECT_TYPE, 'post');

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$post_id
        );

        $this->assertTrue($post->is('hidden'));
    }

    /**
     * Test that access settings are inherited from the parent post
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInheritFromParentPost()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$page_id
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$sub_page_id
        );

        $this->assertTrue($post->is('hidden'));
    }

    /**
     * Test that access is denied to create a new post of a specific post type
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDenyCreateNewPost()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(Type::OBJECT_TYPE, 'aam_test');

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/create', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        register_post_type('aam_test', array(
            'label'        => __('AAM Test', AAM_KEY),
            'description'  => __('Just for testing purposes', AAM_KEY)
        ));

        $this->assertEquals(
            get_post_type_object('aam_test')->cap->create_posts, 'do_not_allow'
        );
    }

    /**
     * Test the ability to edit term
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEditTermAccessOption()
    {
        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/edit', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('edit_term', self::$term_id));
    }

    /**
     * Test the ability to delete term
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDeleteTermAccessOption()
    {
        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/delete', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('delete_term', self::$term_id));
    }

    /**
     * Test the ability to assign term
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAssignTermAccessOption()
    {
        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/assign', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('assign_term', self::$term_id));
    }

    /**
     * Test that term filter is working as expected
     *
     * There are multiple different ways to fetch the list of terms and this is
     * defined by the $fields argument WP_Term_Query::__construct.
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testFilterTerms()
    {
        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'ids',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(in_array(self::$term_id, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>slug',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(self::$term_id, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>name',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(self::$term_id, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>parent',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(self::$term_id, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'all',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertCount(0, array_filter($terms, function($term) {
            return $term->term_id === self::$term_id;
        }));
    }

    /**
     * Test that access is denied to browse the category
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTermBrowseAccessOption()
    {
        global $wp_query;

        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/restricted', true)->save());

        $wp_query->is_category = true;
        $wp_query->queried_object = get_term(self::$term_id, 'category');

        // Override the default handlers so we can suppress die exit
        add_filter('wp_die_handler', function() {
            return function($message, $title) {
                _default_wp_die_handler($message, $title, array('exit' => false));
            };
        }, PHP_INT_MAX);

        // Capture the WP Die message
        ob_start();
        do_action('wp');
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString(
            'Access denied to access this category', $content
        );

        // Reset WP Query
        remove_all_filters('wp_die_handler', PHP_INT_MAX);

        unset($wp_query->is_category);
        unset($wp_query->queried_object);
    }

    /**
     * Making sure that AAM returns correct object when user is switched
     *
     * @return void
     *
     * @access public
     * @version 6.0.4
     */
    public function testCorrectObjectOnSubjectSwitch()
    {
        // Define dummy access settings for a post
        $post = AAM::getUser()->getObject(Type::OBJECT_TYPE, 'post');
        $post->updateOptionItem('post/restricted', true)->save();

        $this->assertTrue($post->is('restricted', 'post'));

        // Change user to visitor
        wp_set_current_user(0);

        $subject = AAM::getUser();
        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));

        $post = $subject->getObject(Type::OBJECT_TYPE, 'post');
        $this->assertFalse($post->is('restricted', 'post'));

        // Reset to default
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);
    }

}