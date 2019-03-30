<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend metaboxes & widgets manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Metabox extends AAM_Backend_Feature_Abstract {

    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_metaboxes')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_metaboxes'));
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

       $object = AAM_Backend_Subject::getInstance()->getObject('metabox');

       foreach($items as $item) {
           $object->save($item, $status);
       }
       
       return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('metabox');
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/metabox.phtml';
    }
    
    /**
     * 
     * @global type $wp_post_types
     * @return type
     */
    public function prepareInitialization() {
        global $wp_post_types;

        AAM_Core_API::deleteOption('aam_metabox_cache');
        
        $endpoints = array();
        
        foreach (array_merge(array('widgets'), array_keys($wp_post_types)) as $type) {
            if ($type === 'widgets') {
                $endpoints[] = add_query_arg('init', 'metabox', admin_url('index.php'));
            } elseif ($wp_post_types[$type]->show_ui) {
                $endpoints[] = add_query_arg(
                    'init', 'metabox', admin_url('post-new.php?post_type=' . $type)
                );
            }
        }
        
        return wp_json_encode(
            array(
                'status'    => 'success',
                'endpoints' => $endpoints
            )
        );
    }
    
    /**
     * Initialize metabox list
     * 
     * @param string $post_type
     * 
     * @return void
     * 
     * @access public
     */
    public function initialize($post_type) {
        $cache = $this->getMetaboxList();
        
        if ($post_type === 'dashboard') {
            $this->collectWidgets($cache);
        } else {
            $this->collectMetaboxes($post_type, $cache);
        }
        
        AAM_Core_API::updateOption('aam_metabox_cache', $cache);
    }

    /**
     * Collect dashboard widgets
     * 
     * @global type $wp_registered_widgets
     * 
     * @return void
     * 
     * @access protected
     */
    protected function collectWidgets(&$cache) {
        global $wp_registered_widgets;

        if (!isset($cache['widgets'])) {
            $cache['widgets'] = array();
        }

        //get frontend widgets
        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $data) {
                if (is_object($data['callback'][0])) {
                    $callback = get_class($data['callback'][0]);
                } elseif (is_string($data['callback'][0])) {
                    $callback = $data['callback'][0];
                } else {
                    $callback = isset($data['classname']) ? $data['classname'] : null;
                }

                if (!is_null($callback)) { //exclude any junk
                    $cache['widgets'][$callback] = array(
                        'title' => wp_strip_all_tags($data['name']),
                        'id'    => $callback
                    );
                }
            }
        }

        //now collect Admin Dashboard Widgets
        $this->collectMetaboxes('dashboard', $cache);
    }
    
    /**
     * Collect metaboxes
     * 
     * @param type $post_type
     * @param type $cache
     * 
     * @return void
     * 
     * @access protected
     * @global array $wp_meta_boxes
     */
    protected function collectMetaboxes($post_type, &$cache) {
        global $wp_meta_boxes;

        if (!isset($cache[$post_type])) {
            $cache[$post_type] = array();
        }
        
        if (isset($wp_meta_boxes[$post_type]) && is_array($wp_meta_boxes[$post_type])) {
            foreach ($wp_meta_boxes[$post_type] as $levels) {
                if (is_array($levels)) {
                    foreach ($levels as $boxes) {
                        if (is_array($boxes)) {
                            foreach ($boxes as $data) {
                                if (trim($data['id'])) { //exclude any junk
                                    $cache[$post_type][$data['id']] = array(
                                        'id'    => $data['id'],
                                        'title' => wp_strip_all_tags($data['title'])
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 
     * @return type
     */
    public function getMetaboxList() {
        global $wp_post_types;
        
        $cache   = AAM_Core_API::getOption('aam_metabox_cache', array());
        $subject = AAM_Backend_Subject::getInstance();
        
        //if visitor, return only frontend widgets
        if ($subject->getUID() === AAM_Core_Subject_Visitor::UID) {
            if (!empty($cache['widgets'])) {
                $response = array('widgets' => $cache['widgets']);
            } else {
                $response = array();
            }
        } else {
            $response = $cache;
        }
        
        //filter non-existing metaboxes
        foreach(array_keys($response) as $id) {
            if (!in_array($id, array('dashboard', 'widgets'), true) 
                    && empty($wp_post_types[$id])) {
                unset($response[$id]);
            }
        }
        
        return $response;
    }
    
     /**
     * 
     * @return type
     */
    protected function isOverwritten() {
        $object = AAM_Backend_Subject::getInstance()->getObject('metabox');
        
        return $object->isOverwritten();
    }

    /**
     * Register metabox feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'metabox',
            'position'   => 10,
            'title'      => __('Metaboxes & Widgets', AAM_KEY),
            'capability' => 'aam_manage_metaboxes',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'      => 'core.settings.backendAccessControl',
            'view'        => __CLASS__
        ));
    }

}