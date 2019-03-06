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
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Menu extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        if (!current_user_can('aam_manage_admin_menu')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_admin_menu'));
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function save() {
       $items  = AAM_Core_Request::post('items', array());
       $status = AAM_Core_Request::post('status');

       $object = AAM_Backend_Subject::getInstance()->getObject('menu');

       foreach($items as $item) {
           $object->updateOptionItem($item, $status);
       }
       
       $object->save();

       return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('menu');
    }

    /**
     * Get subject's menu
     * 
     * Based on the list of capabilities that current subject has, prepare
     * complete menu list and return it.
     * 
     * @return array
     * 
     * @access public
     * @global array  $menu
     */
    public function getMenu() {
        $menu = json_decode(base64_decode(AAM_Core_Request::post('menu')), 1);
        
        $response = array();
        
        //let's create menu list with submenus
        if (!empty($menu)) {
            $object = AAM_Backend_Subject::getInstance()->getObject('menu');
            foreach ($menu as $item) {
                if (preg_match('/^separator/', $item[2])) {
                    continue; //skip separator
                }

                $submenu = $this->getSubmenu($item[2]);

                $allowed = AAM_Backend_Subject::getInstance()->hasCapability($item[1]);

                if ($allowed || count($submenu) > 0) {
                    $menuItem = array(
                        //add menu- prefix to define that this is the top level menu
                        //WordPress by default gives the same menu id to the first
                        //submenu
                        'id'         => 'menu-' . $item[2],
                        'name'       => $this->filterMenuName($item[0]),
                        'submenu'    => $submenu,
                        'capability' => $item[1],
                        'crc32'      => crc32('menu-' . $item[2]),
                    );
                    $menuItem['checked'] = $object->has($menuItem['id']) || $object->has($menuItem['crc32']);
                    $response[] = $menuItem;
                }
            }
        }

        return $response;
    }
    
    /**
     * 
     * @param array $menu
     * @return array
     */
    protected function normalizeItem($menu) {
        if (strpos($menu, 'customize.php') === 0) {
            $menu = 'customize.php';
        }
        
        return $menu;
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/menu.phtml';
    }

    /**
     * Prepare filtered submenu
     * 
     * @param string $menu
     * 
     * @return array
     * 
     * @access protected
     * @global array  $submenu
     */
    protected function getSubmenu($menu) {
        $submenu = json_decode(base64_decode(AAM_Core_Request::post('submenu')), 1);
        $object  = AAM_Backend_Subject::getInstance()->getObject('menu');
        
        $response  = array();
        $subject   = AAM_Backend_Subject::getInstance();
        $isDefault = ($subject->getUID() === AAM_Core_Subject_Default::UID);
        
        if (array_key_exists($menu, $submenu) && is_array($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                if ($subject->hasCapability($item[1]) || $isDefault) {
                    $id = $this->normalizeItem($item[2]);
                    $menuItem = array(
                        'id'         => $id,
                        'name'       => $this->filterMenuName($item[0]),
                        'capability' => $item[1],
                        'crc32'      => crc32($id)
                    );
                    $menuItem['checked'] = $object->has($menuItem['id']) || $object->has($menuItem['crc32']);
                    $response[] = $menuItem;
                }
            }
        }

        return $response;
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
     */
    protected function filterMenuName($name) {
        $filtered = trim(wp_strip_all_tags($name));
        
        return preg_replace('/([\d]+)$/', '', $filtered);
    }

    /**
     * 
     * @param type $subs
     * @return boolean
     */
    protected function hasSubmenuChecked($subs) {
        $has = false;
        
        if (!empty($subs)) {
            foreach($subs as $submenu) {
                if ($submenu['checked']) {
                    $has = true;
                    break;
                }
            }
        }
        
        return $has;
    }
    
    /**
     * Check inheritance status
     * 
     * Check if menu settings are overwritten
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isOverwritten() {
        $object = AAM_Backend_Subject::getInstance()->getObject('menu');
        
        return $object->isOverwritten();
    }

    /**
     * Register Menu feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'admin_menu',
            'position'   => 5,
            'title'      => __('Backend Menu', AAM_KEY),
            'capability' => 'aam_manage_admin_menu',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'core.settings.backendAccessControl',
            'view'       => __CLASS__
        ));
    }

}