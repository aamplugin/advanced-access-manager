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
    public function refreshList() {
        global $wp_post_types;

        AAM_Core_API::deleteOption('aam_metabox_cache');
        $type_list = array_keys($wp_post_types);

        //used to retrieve the list of all wigets on the frontend
        array_unshift($type_list, 'widgets');

        foreach ($type_list as $type) {
            if ($type == 'widgets') {
                $url = add_query_arg('init', 'metabox', admin_url('index.php'));
            } else {
                $url = add_query_arg(
                        'init', 
                        'metabox', 
                        admin_url('post-new.php?post_type=' . $type)
                );
            }
            
            //grab metaboxes
            AAM_Core_API::cURL($this->addHttpPasswd($url));
        }
        
        return json_encode(array('status' => 'success'));
    }
    
    /**
     *
     * @global type $wp_post_types
     * @return type
     */
    public function initURL() {
        //grab metaboxes
        $url = $this->addHttpPasswd(AAM_Core_Request::post('url'));
        AAM_Core_API::cURL(add_query_arg('init', 'metabox', $url));
        
        return json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @param type $url
     * @return type
     */
    protected function addHttpPasswd($url) {
        $htpasswd = AAM_Core_Config::get('htpasswd');
        
        if (!empty($htpasswd['user']) && !empty($htpasswd['pass'])) {
            $url = preg_replace(
                    '/^(http[s]?:\/\/)/', 
                    "$1{$htpasswd['user']}:{$htpasswd['pass']}@", 
                    $url
            );
        }
        
        return $url;
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
                    $callback = null;
                }

                if (!is_null($callback)) { //exclude any junk
                    $cache['widgets'][$callback] = array(
                        'title' => strip_tags($data['name']),
                        'id' => $callback
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
                                        'id' => $data['id'],
                                        'title' => strip_tags($data['title'])
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
        if ($subject->getUID() == AAM_Core_Subject_Visitor::UID) {
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
            if (!in_array($id, array('dashboard', 'widgets')) 
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
            'option'      => 'backend-access-control',
            'view'        => __CLASS__
        ));
    }

}