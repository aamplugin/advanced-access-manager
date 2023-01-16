<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend menu manager
 *
 * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/240
 * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.5
 */
class AAM_Backend_Feature_Main_Menu
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_admin_menu';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Menu::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/menu.php';

    /**
     * Save menu settings
     *
     * @return string
     *
     * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.9
     */
    public function save()
    {
        $status = $this->getFromPost('status');

        $object = AAM_Backend_Subject::getInstance()->getObject(
            self::OBJECT_TYPE, null, true
        );

        foreach (AAM_Core_Request::post('items', array()) as $item) {
            $object->updateOptionItem($item, !empty($status));
        }

        $result = $object->save();

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Get admin menu
     *
     * Based on the list of capabilities that current subject has, prepare
     * complete menu list and return it.
     *
     * @return array
     *
     * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/240
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.5
     */
    public function getMenu()
    {
        $response = array();

        $cache    = AAM_Service_AdminMenu::getInstance()->getMenuCache();
        $subject  = AAM_Backend_Subject::getInstance();

        // Create menu list with submenus
        if (!empty($cache)) {
            $object = $subject->getObject(self::OBJECT_TYPE);

            foreach ($cache['menu'] as $item) {
                if (preg_match('/^separator/', $item['id'])) {
                    continue; //skip separator
                }

                $response[] = array(
                    // Add menu- prefix to define that this is the top level menu.
                    // WordPress by default gives the same menu id to the first
                    // submenu
                    'id'         => 'menu-' . $item['id'],
                    'uri'        => $this->prepareAdminURI($item['id']),
                    'name'       => $this->filterMenuName($item['name']),
                    'submenu'    => $this->getSubmenu($item['id'], $cache['submenu']),
                    'capability' => $item['cap'],
                    'checked'    => $object->isRestricted('menu-' . $item['id'])
                );
            }
        }

        return $response;
    }

    /**
     * Normalize menu item
     *
     * @param string $menu
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function normalizeItem($menu)
    {
        if (strpos($menu, 'customize.php') === 0) {
            $menu = 'customize.php';
        }

        return $menu;
    }

    /**
     * Prepare filtered submenu
     *
     * @param string $menu
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getSubmenu($menu, $submenu)
    {
        $response = array();

        $object = AAM_Backend_Subject::getInstance()->getObject(self::OBJECT_TYPE);

        if (array_key_exists($menu, $submenu) && is_array($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                $id = $this->normalizeItem($item[2]);

                $response[] = array(
                    'id'         => $id,
                    'uri'        => $this->prepareAdminURI($item[2]),
                    'name'       => $this->filterMenuName($item[0]),
                    'capability' => $item[1],
                    'checked'    => $object->isRestricted($id)
                );
            }
        }

        return $response;
    }

    /**
     * Prepare admin URI for the menu item
     *
     * @param string $resource
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareAdminURI($resource)
    {
        $hook = get_plugin_page_hook($resource, 'admin.php');
        $uri  = (!empty($hook) ? 'admin.php?page=' . $resource : $resource);

        return '/wp-admin/' . $uri;
    }

    /**
     * Filter menu name
     *
     * Strip any HTML tags from the menu name and also remove the trailing
     * numbers in case of Plugin or Comments menu name.
     *
     * @param string $name
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function filterMenuName($name)
    {
        $filtered = trim(wp_strip_all_tags(
            preg_replace('@<(span)[^>]*?>.*?</\\1>@si', '', $name),
            true
        ));

        return preg_replace('/([\d]+)$/', '', $filtered);
    }

    /**
     * Check if there is at least one submenu restricted
     *
     * @param array $subs
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function hasSubmenuChecked($subs)
    {
        $has = false;

        if (!empty($subs)) {
            foreach ($subs as $submenu) {
                if ($submenu['checked']) {
                    $has = true;
                    break;
                }
            }
        }

        return $has;
    }

    /**
     * Register Admin Menu feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'admin_menu',
            'position'   => 5,
            'title'      => __('Backend Menu', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}