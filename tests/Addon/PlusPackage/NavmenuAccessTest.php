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
    AAM\AddOn\PlusPackage\Hooks\ContentHooks;

/**
 * Test cases for the navigation menu filtering
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.5.0
 */
class NavmenuAccessTest extends TestCase
{
    use ResetTrait;

    protected static $nav_menu_id;
    protected static $top_page_id;
    protected static $sub_page_id;
    protected static $top_nav_id;
    protected static $sub_nav_id;
    protected static $term_id;
    protected static $nav_category_id;

    /**
     * @inheritDoc
     */
    private static function _setUpBeforeClass()
    {
        self::$nav_menu_id = wp_create_nav_menu('Unittest Nav');

        self::$top_page_id = wp_insert_post(array(
            'post_title'  => 'Top Page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));

        self::$sub_page_id = wp_insert_post(array(
            'post_title'  => 'Sub Page',
            'post_type'   => 'page',
            'post_parent' => self::$top_page_id,
            'post_status' => 'publish'
        ));

        // Build the navigation menu
        self::$top_nav_id = wp_update_nav_menu_item(self::$nav_menu_id, 0, array (
                'menu-item-title' => 'Top Menu Item',
                'menu-item-object' => 'page',
                'menu-item-parent-id' => 0,
                'menu-item-object-id' => self::$top_page_id,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'
        ));

        self::$sub_nav_id = wp_update_nav_menu_item(self::$nav_menu_id, 0, array (
            'menu-item-title' => 'Sub Menu Item',
            'menu-item-object' => 'page',
            'menu-item-parent-id' => self::$top_nav_id,
            'menu-item-object-id' => self::$sub_page_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish'
        ));

        $term          = wp_insert_term('Some Category', 'category');
        self::$term_id = $term['term_id'];

        self::$nav_category_id = wp_update_nav_menu_item(self::$nav_menu_id, 0, array (
            'menu-item-title' => 'Category',
            'menu-item-object' => 'category',
            'menu-item-object-id' => self::$term_id,
            'menu-item-type' => 'taxonomy',
            'menu-item-status' => 'publish'
        ));
    }

    /**
     * Test that navigation menu is filtered as expected
     *
     * This test completely hides the entire navigation menu
     *
     * @return void
     *
     * @access public
     * @version 6.5.0
     */
    public function testHideNavMenu()
    {
        $object = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, self::$nav_menu_id . '|nav_menu'
        );

       $this->assertTrue($object->updateOptionItem('term/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = wp_get_nav_menu_items('Unittest Nav');

        $this->assertFalse($menu);
    }

    /**
     * Test that navigation menu is filtered as expected
     *
     * This test completely hides the entire navigation menu
     *
     * @return void
     *
     * @access public
     * @version 6.5.0
     */
    public function testHideNavMenuBranch()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$nav_menu_id
        );

       $this->assertTrue($object->updateOptionItem('hidden', array(
           'enabled'  => true,
           'frontend' => true
       ))->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = array_map(function($item) {
            return $item->ID;
        }, wp_get_nav_menu_items('Unittest Nav'));

        $this->assertFalse(in_array(self::$top_nav_id, $menu, true));
        $this->assertFalse(in_array(self::$sub_nav_id, $menu, true));
    }

    /**
     * Test that navigation menu does not contain hidden page
     *
     * The page can be explicitly hidden and that is why it is also should be hidden
     * from the navigation menu
     *
     * @return void
     *
     * @access public
     * @version 6.5.0
     */
    public function testHideNavMenuItemImplicitly()
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, self::$top_page_id
        );

       $this->assertTrue($object->updateOptionItem('hidden', array(
           'enabled'  => true,
           'frontend' => true
       ))->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = array_map(function($item) {
            return $item->object_id;
        }, wp_get_nav_menu_items(self::$nav_menu_id));

        $this->assertFalse(in_array(self::$top_page_id, $menu, true));
    }

    /**
     * Test that navigation menu does not contain hidden category
     *
     * The category can be explicitly hidden and that is why it is also should be hidden
     * from the navigation menu
     *
     * @return void
     *
     * @access public
     * @version 6.5.0
     */
    public function testHideNavMenuCategoryItemImplicitly()
    {
        $object = AAM::getUser()->getObject(
            Term::OBJECT_TYPE, self::$term_id . '|category'
        );

       $this->assertTrue($object->updateOptionItem('term/hidden', array(
           'enabled'  => true,
           'frontend' => true
       ))->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = array_map(function($item) {
            return $item->object_id;
        }, wp_get_nav_menu_items(self::$nav_menu_id));

        $this->assertFalse(in_array(self::$term_id, $menu, true));
    }

}