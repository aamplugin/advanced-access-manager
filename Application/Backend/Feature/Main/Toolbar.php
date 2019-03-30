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
class AAM_Backend_Feature_Main_Toolbar extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_admin_toolbar')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_admin_toolbar'));
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

       $object = AAM_Backend_Subject::getInstance()->getObject('toolbar');

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
        return AAM_Backend_Subject::getInstance()->resetObject('toolbar');
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
    public function getToolbar() {
        return json_decode(base64_decode(AAM_Core_Request::post('toolbar')));
    }
    
    /**
     * 
     * @param type $branch
     * @return type
     */
    public function getAllChildren($branch) {
        $children = array();
        
        foreach($branch->children as $child) {
            if (empty($child->type) || !in_array($child->type, array('container', 'group'), true)) {
                $children[] = $child;
            }
            if(!empty($child->children)) {
                $children = array_merge($children, $this->getAllChildren($child));
            }
        }
        
        return $children;
    }
    
    /**
     * 
     * @param type $node
     * @return type
     */
    public function normalizeTitle($node) {
        return ucwords(
            trim(
                preg_replace(
                    '/[\d]/', 
                    '', 
                    wp_strip_all_tags(!empty($node->title) ? $node->title : $node->id)
                )
            )
        );
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/toolbar.phtml';
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
        $object = AAM_Backend_Subject::getInstance()->getObject('toolbar');
        
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
            'uid'        => 'toolbar',
            'position'   => 6,
            'title'      => __('Admin Toolbar', AAM_KEY),
            'capability' => 'aam_manage_admin_toolbar',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'core.settings.backendAccessControl,core.settings.frontendAccessControl',
            'view'       => __CLASS__
        ));
    }

}