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
    AAM\UnitTest\Libs\AuthUserTrait,
    AAM\AddOn\PlusPackage\Object\Term,
    AAM\AddOn\PlusPackage\Object\Type,
    AAM\AddOn\PlusPackage\Object\Taxonomy,
    AAM\AddOn\PlusPackage\Hooks\ContentHooks;

/**
 * Test cases for the Plus Package content access management
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class ContentAccessTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_PAGE_LEVEL_1_ID
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_PAGE_LEVEL_2_ID
        );

        $this->assertTrue($post->is('hidden'));
    }

    /**
     * Test access settings adjusting based on [ACTION]_OTHERS access option
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAdjustedPostAccessSettings()
    {
        // Make other user as the owner of the post
        wp_update_post(array(
            'ID'          => AAM_UNITTEST_POST_ID,
            'post_author' => AAM_UNITTEST_JOHN_ID
        ));

        $user   = AAM::getUser();
        $object = $user->getObject(Type::OBJECT_TYPE, 'post');

        foreach(array('edit', 'hidden', 'delete', 'publish', 'restricted') as $act) {
            $object->updateOptionItem("post/{$act}_others", true);
        }

        // Check if save returns positive result
        $this->assertTrue($object->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
        );

        $this->assertTrue($post->is('hidden'));
        $this->assertTrue($post->is('restricted'));
        $this->assertFalse($post->isAllowedTo('edit'));
        $this->assertFalse($post->isAllowedTo('delete'));
        $this->assertFalse($post->isAllowedTo('publish'));

        // Reset back to the original author
        wp_update_post(array(
            'ID'          => AAM_UNITTEST_POST_ID,
            'post_author' => AAM_UNITTEST_AUTH_USER_ID
        ));
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
     * Test that access is denied to edit or create a new term of a specific taxonomy
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDenyCreateOrEditTaxonomy()
    {
        $user   = AAM::getUser();
        $object = $user->getObject(Taxonomy::OBJECT_TYPE, 'aam_test');

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('term/edit', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        register_taxonomy('aam_test', 'post', array('hierarchical' => true));

        $this->assertEquals(
            get_taxonomy('aam_test')->cap->edit_terms, 'do_not_allow'
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/edit', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('edit_term', AAM_UNITTEST_CATEGORY_ID));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/delete', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('delete_term', AAM_UNITTEST_CATEGORY_ID));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/assign', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $this->assertFalse(current_user_can('assign_term', AAM_UNITTEST_CATEGORY_ID));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
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

        $this->assertFalse(in_array(AAM_UNITTEST_CATEGORY_ID, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>slug',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(AAM_UNITTEST_CATEGORY_ID, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>name',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(AAM_UNITTEST_CATEGORY_ID, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'id=>parent',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertFalse(array_key_exists(AAM_UNITTEST_CATEGORY_ID, $terms));

        $terms = get_terms(array(
            'number'     => 0,
            'fields'     => 'all',
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        $this->assertCount(0, array_filter($terms, function($term) {
            return $term->term_id === AAM_UNITTEST_CATEGORY_ID;
        }));
    }

    /**
     * Test that navigation menu is filtered as expected
     *
     * @return void
     *
     * @access public
     * @version 5.0.0
     */
    public function testFilterNavMenu()
    {
        $user = AAM::getUser();
        $role = $user->getParent(); // Administrator role

        $object = $role->getObject(
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

       $this->assertTrue($object->updateOptionItem('term/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = wp_get_nav_menu_items(AAM_UNITTEST_NAV_MENU_NAME);

        $this->assertCount(0, array_filter($menu, function($item) {
            return $item->object_id === AAM_UNITTEST_CATEGORY_ID && $item->object === 'category';
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        $this->assertTrue($object->updateOptionItem('term/restricted', true)->save());

        $wp_query->is_category = true;
        $wp_query->queried_object = get_term(AAM_UNITTEST_CATEGORY_ID, 'category');

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
        wp_set_current_user(AAM_UNITTEST_AUTH_USER_ID);
    }

}