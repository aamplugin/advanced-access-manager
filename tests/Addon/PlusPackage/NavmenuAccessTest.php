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
 * Test cases for the navigation menu filtering
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.5.0
 */
class NavmenuAccessTest extends TestCase
{
    use ResetTrait;

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
            Term::OBJECT_TYPE, AAM_UNITTEST_NAV_MENU_ID . '|nav_menu'
        );

       $this->assertTrue($object->updateOptionItem('term/hidden', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();
        ContentHooks::bootstrap()->resetCache();

        $menu = wp_get_nav_menu_items(AAM_UNITTEST_NAV_MENU_NAME);

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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_NAV_MENU_FIRST_MENU_ID
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
        }, wp_get_nav_menu_items(AAM_UNITTEST_NAV_MENU_NAME));

        $this->assertFalse(in_array(AAM_UNITTEST_NAV_MENU_FIRST_MENU_ID, $menu, true));
        $this->assertFalse(in_array(AAM_UNITTEST_NAV_MENU_FIRST_MENU_SUB1_ID, $menu, true));
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
            AAM_Core_Object_Post::OBJECT_TYPE, AAM_UNITTEST_PAGE_ID
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
        }, wp_get_nav_menu_items(AAM_UNITTEST_NAV_MENU_NAME));

        $this->assertFalse(in_array(AAM_UNITTEST_PAGE_ID, $menu, true));
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
            Term::OBJECT_TYPE, AAM_UNITTEST_CATEGORY_LEVEL_1_ID . '|category'
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
        }, wp_get_nav_menu_items(AAM_UNITTEST_NAV_MENU_NAME));

        $this->assertFalse(in_array(AAM_UNITTEST_CATEGORY_LEVEL_1_ID, $menu, true));
    }

}