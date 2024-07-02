<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    AAM_Framework_Manager,
    AAM\AddOn\PlusPackage\Main,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\AddOn\PlusPackage\Object\System;

/**
 * Test default term(s) assignment to a post
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class DefaultTermTest extends TestCase
{
    use ResetTrait;

    protected static $term_id;
    protected static $media_term_id;
    protected static $media_term_b_id;
    protected static $post_id;
    protected static $media_id;
    protected static $page_id;
    protected static $sub_page_id;
    protected static $term_top_id;
    protected static $term_sub_id;
    protected static $tag_a_id;
    protected static $tag_b_id;

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

        self::$media_id = wp_insert_post(array(
            'post_title'  => 'Plus Package Media',
            'post_type'   => 'attachment',
            'post_status' => 'publish'
        ));

        AAM_Framework_Manager::configs()->set_config(
            'core.settings.mediaCategory', true
        );
        Main::bootstrap()->registerTaxonomies();

        $media_term          = wp_insert_term('Media Category', 'media_category');
        self::$media_term_id = $media_term['term_id'];

        $media_term_b          = wp_insert_term('Media Category B', 'media_category');
        self::$media_term_b_id = $media_term_b['term_id'];

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

        self::$term_top_id = wp_insert_term('Top Level', 'category')['term_id'];
        self::$term_sub_id = wp_insert_term(
            'Top Level', 'category', array('parent' =>  self::$term_top_id)
        )['term_id'];

        self::$tag_a_id = wp_insert_term('Tag A', 'post_tag')['term_id'];
        self::$tag_b_id = wp_insert_term('Tag B', 'post_tag')['term_id'];
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
     * Test the new default category is assigned to post that has no categories
     * attached
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPostSaveCategoryAssignment()
    {
        // Get original post terms
        $terms = wp_get_object_terms(self::$post_id, 'category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(self::$post_id, $terms, 'category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.category',
                intval(self::$term_top_id)
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => self::$post_id
        ));

        $new_terms = wp_get_object_terms(self::$post_id, 'category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(self::$term_top_id), $new_terms);

        // Restore original categories
        wp_set_object_terms(self::$post_id, $terms, 'category');
    }

    /**
     * Test the multiple new default category are assigned to a new post
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testPostCreateMultipleTagsAssignment()
    {
        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.post_tag',
                array(self::$tag_a_id, self::$tag_b_id)
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        $id = wp_insert_post(array(
            'post_title'  => 'Unit Test Automation',
            'post_type'   => 'post',
            'post_status' => 'draft'
        ));

        $this->assertTrue(is_int($id));

        $new_terms = wp_get_object_terms($id, 'post_tag', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(self::$tag_a_id), $new_terms);
        $this->assertContains(intval(self::$tag_b_id), $new_terms);

        wp_delete_post($id, true);
    }

    /**
     * Test the new default category is not assigned to post that has already
     * category(s) attached
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testPostSaveCategoryPreserved()
    {
        // Get original post terms
        $terms = wp_get_object_terms(self::$post_id, 'category', array(
            'fields' => 'ids'
        ));

        // Make sure that we have at least one category attached
        $this->assertGreaterThanOrEqual(1, count($terms));

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.category', self::$term_top_id
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => self::$post_id
        ));

        $new_terms = wp_get_object_terms(self::$post_id, 'category', array(
            'fields' => 'ids'
        ));

        $this->assertEquals($terms, $new_terms);
    }

    /**
     * Test assigning default category to attachment when none is specified
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAttachmentUpdateCategoryAssignment()
    {
        // Enable media category
        $this->assertTrue(AAM_Framework_Manager::configs()->set_config(
            'core.settings.mediaCategory', true
        ));

        // Get original post terms
        $terms = wp_get_object_terms(self::$media_id, 'media_category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(self::$media_id, $terms, 'media_category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                self::$media_term_id
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => self::$media_id
        ));

        $new_terms = wp_get_object_terms(self::$media_id, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(self::$media_term_id, $new_terms);

        // Restore original categories
        wp_set_object_terms(self::$media_id, $terms, 'media_category');
    }

    /**
     * Test assigning multiple default categories to attachment when none is specified
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testAttachmentUpdateMultipleCategoriesAssignment()
    {
        // Enable media category
        $this->assertTrue(AAM_Framework_Manager::configs()->set_config(
            'core.settings.mediaCategory', true
        ));

        // Get original post terms
        $terms = wp_get_object_terms(self::$media_id, 'media_category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(self::$media_id, $terms, 'media_category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                array(
                    intval(self::$media_term_id),
                    intval(self::$media_term_b_id)
                )
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => self::$media_id
        ));

        $new_terms = wp_get_object_terms(self::$media_id, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(self::$media_term_id), $new_terms);
        $this->assertContains(intval(self::$media_term_b_id), $new_terms);

        // Restore original categories
        wp_set_object_terms(self::$media_id, $terms, 'media_category');
    }

    /**
     * Test assigning default category to new attachment
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAttachmentAddCategoryAssignment()
    {
        // Enable media category
        $this->assertTrue(AAM_Framework_Manager::configs()->set_config(
            'core.settings.mediaCategory', true
        ));

        Main::bootstrap()->registerTaxonomies();

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category', self::$media_term_id
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        $id = wp_insert_post(array(
            'post_type'  => 'attachment',
            'post_title' => 'Dummy Attachment'
        ));

        $new_terms = wp_get_object_terms($id, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(self::$media_term_id, $new_terms);

        // Restore original categories
        wp_delete_post($id, true);
    }

    /**
     * Test assigning multiple default categories to a new attachment
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAttachmentAddMultipleCategoriesAssignment()
    {
        // Enable media category
        $this->assertTrue(AAM_Framework_Manager::configs()->set_config(
            'core.settings.mediaCategory', true
        ));

        Main::bootstrap()->registerTaxonomies();

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                array(
                    self::$media_term_id,
                    self::$media_term_b_id,
                )
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        $id = wp_insert_post(array(
            'post_type'  => 'attachment',
            'post_title' => 'Dummy Attachment'
        ));

        $new_terms = wp_get_object_terms($id, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(self::$media_term_id, $new_terms);
        $this->assertContains(self::$media_term_b_id, $new_terms);

        // Restore original categories
        wp_delete_post($id, true);
    }

    /**
     * Test that default_category option is adjusted to a new value
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testGetDefaultCategoryOption()
    {
        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.category', self::$term_sub_id
            )->save()
        );

        $this->assertEquals(
            self::$term_sub_id, get_option('default_category')
        );
    }

}