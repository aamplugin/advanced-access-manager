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

       return json_encode(array('status' => 'success'));
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
        return AAM_Core_API::getOption('aam_toolbar_cache', array());
    }
    
    /**
     * 
     * @param type $branch
     * @return type
     */
    public function getAllChildren($branch) {
        $children = array();
        
        foreach($branch->children as $child) {
            if (empty($child->type) || !in_array($child->type, array('container', 'group'))) {
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
                    strip_tags(!empty($node->title) ? $node->title : $node->id)
                )
            )
        );
    }
    
    /**
     * 
     * @return type
     */
    public function refreshList() {
        // reset cache
        AAM_Core_API::deleteOption('aam_toolbar_cache');
        
        //grab toolbar itesm
        AAM_Core_API::cURL($this->addHttpPasswd(
                add_query_arg('init', 'toolbar', admin_url('index.php')))
        );
        
        return json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @param type $url
     * @return type
     */
    protected function addHttpPasswd($url) {
        $htpasswd = AAM_Core_Config::get('feature.toolbar.htpasswd');
        
        if (!empty($htpasswd['user']) && !empty($htpasswd['pass'])) {
            $url = preg_replace(
                '/^(http[s]?:\/\/)/', "$1{$htpasswd['user']}:{$htpasswd['pass']}@", $url
            );
        }
        
        return $url;
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
            'capability' => 'aam_manage_toolbar',
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