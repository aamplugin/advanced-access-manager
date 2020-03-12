<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    AAM_Core_Config,
    AAM\AddOn\PlusPackage\Main,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait,
    AAM\AddOn\PlusPackage\Object\System;

/**
 * Test default term(s) assignment to a post
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class DefaultTermTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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
        $terms = wp_get_object_terms(AAM_UNITTEST_POST_ID, 'category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(AAM_UNITTEST_POST_ID, $terms, 'category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.category',
                intval(AAM_UNITTEST_CATEGORY_LEVEL_1_ID)
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => AAM_UNITTEST_POST_ID
        ));

        $new_terms = wp_get_object_terms(AAM_UNITTEST_POST_ID, 'category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(AAM_UNITTEST_CATEGORY_LEVEL_1_ID), $new_terms);

        // Restore original categories
        wp_set_object_terms(AAM_UNITTEST_POST_ID, $terms, 'category');
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
                array(
                    intval(AAM_UNITTEST_TAG_ID),
                    intval(AAM_UNITTEST_TAG_ID_B)
                )
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

        $this->assertContains(intval(AAM_UNITTEST_TAG_ID), $new_terms);
        $this->assertContains(intval(AAM_UNITTEST_TAG_ID_B), $new_terms);

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
        $terms = wp_get_object_terms(AAM_UNITTEST_POST_ID, 'category', array(
            'fields' => 'ids'
        ));

        // Make sure that we have at least one category attached
        $this->assertGreaterThanOrEqual(1, count($terms));

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.post.category', AAM_UNITTEST_CATEGORY_LEVEL_1_ID
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => AAM_UNITTEST_POST_ID
        ));

        $new_terms = wp_get_object_terms(AAM_UNITTEST_POST_ID, 'category', array(
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
        $this->assertTrue(AAM_Core_Config::set('core.settings.mediaCategory', true));
        Main::bootstrap()->registerTaxonomies();

        // Get original post terms
        $terms = wp_get_object_terms(AAM_UNITTEST_ATTACHMENT_ID, 'media_category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(AAM_UNITTEST_ATTACHMENT_ID, $terms, 'media_category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                intval(AAM_UNITTEST_MEDIA_CATEGORY_ID)
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => AAM_UNITTEST_ATTACHMENT_ID
        ));

        $new_terms = wp_get_object_terms(AAM_UNITTEST_ATTACHMENT_ID, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(AAM_UNITTEST_MEDIA_CATEGORY_ID), $new_terms);

        // Restore original categories
        wp_set_object_terms(AAM_UNITTEST_ATTACHMENT_ID, $terms, 'media_category');
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
        $this->assertTrue(AAM_Core_Config::set('core.settings.mediaCategory', true));
        Main::bootstrap()->registerTaxonomies();

        // Get original post terms
        $terms = wp_get_object_terms(AAM_UNITTEST_ATTACHMENT_ID, 'media_category', array(
            'fields' => 'ids'
        ));

        // Remove all the terms from the post(
        wp_remove_object_terms(AAM_UNITTEST_ATTACHMENT_ID, $terms, 'media_category');

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                array(
                    intval(AAM_UNITTEST_MEDIA_CATEGORY_ID),
                    intval(AAM_UNITTEST_MEDIA_CATEGORY_ID_B)
                )
            )->save()
        );

        // Reset all internal cache
        $this->_resetSubjects();

        wp_update_post(array(
            'ID' => AAM_UNITTEST_ATTACHMENT_ID
        ));

        $new_terms = wp_get_object_terms(AAM_UNITTEST_ATTACHMENT_ID, 'media_category', array(
            'fields' => 'ids'
        ));

        $this->assertContains(intval(AAM_UNITTEST_MEDIA_CATEGORY_ID), $new_terms);
        $this->assertContains(intval(AAM_UNITTEST_MEDIA_CATEGORY_ID_B), $new_terms);

        // Restore original categories
        wp_set_object_terms(AAM_UNITTEST_ATTACHMENT_ID, $terms, 'media_category');
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
        $this->assertTrue(AAM_Core_Config::set('core.settings.mediaCategory', true));
        Main::bootstrap()->registerTaxonomies();

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category', AAM_UNITTEST_MEDIA_CATEGORY_ID
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

        $this->assertContains(AAM_UNITTEST_MEDIA_CATEGORY_ID, $new_terms);

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
        $this->assertTrue(AAM_Core_Config::set('core.settings.mediaCategory', true));
        Main::bootstrap()->registerTaxonomies();

        // Set the default category
        $system = AAM::getUser()->getObject(System::OBJECT_TYPE);
        $this->assertTrue(
            $system->updateOptionItem(
                'defaultTerm.attachment.media_category',
                array(
                    AAM_UNITTEST_MEDIA_CATEGORY_ID,
                    AAM_UNITTEST_MEDIA_CATEGORY_ID_B,
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

        $this->assertContains(AAM_UNITTEST_MEDIA_CATEGORY_ID, $new_terms);
        $this->assertContains(AAM_UNITTEST_MEDIA_CATEGORY_ID_B, $new_terms);

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
                'defaultTerm.post.category', AAM_UNITTEST_CATEGORY_LEVEL_2_ID
            )->save()
        );

        $this->assertEquals(
            AAM_UNITTEST_CATEGORY_LEVEL_2_ID, get_option('default_category')
        );
    }

}