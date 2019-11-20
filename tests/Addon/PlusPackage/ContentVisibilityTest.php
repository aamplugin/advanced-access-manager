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
    AAM\AddOn\PlusPackage\Hooks\ContentHooks;

/**
 * Test cases for the Plus Package content visibility management
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class ContentVisibilityTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_PAGE_LEVEL_1_ID
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

        $this->assertFalse(in_array(AAM_UNITTEST_PAGE_LEVEL_2_ID, $posts));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
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

        $this->assertFalse(in_array(AAM_UNITTEST_POST_ID, $posts));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
        );

        // Check if save returns positive result
        $this->assertTrue($object->updateOptionItem('post/hidden', true)->save());

        $post = $user->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_POST_ID
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

        $this->assertContains(AAM_UNITTEST_POST_ID, $posts);
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_ID . '|category'
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

        $this->assertContains(AAM_UNITTEST_POST_ID, $posts);
    }

}