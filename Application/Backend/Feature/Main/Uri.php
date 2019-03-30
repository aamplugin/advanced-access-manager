<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress API manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Uri extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_uri')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_uri'));
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getTable() {
        return wp_json_encode($this->retrieveAllRules());
    }

    /**
     * 
     * @return type
     */
    public function save() {
       $uri   = filter_input(INPUT_POST, 'uri');
       $id    = filter_input(INPUT_POST, 'id');
       $type  = filter_input(INPUT_POST, 'type');
       $value = filter_input(INPUT_POST, 'value');

       $object = AAM_Backend_Subject::getInstance()->getObject('uri');
       
       if (empty($id)) {
           $id = uniqid();
       }
       
       $object->save($id, str_replace(site_url(), '', $uri), $type, $value);

       return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('uri');
    }
    
    /**
     * 
     * @return type
     */
    public function delete() {
        $id     = filter_input(INPUT_POST, 'id');
        $object = AAM_Backend_Subject::getInstance()->getObject('uri');
        
        $object->delete($id);

       return wp_json_encode(array('status' => 'success'));
    }

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/uri.phtml';
    }
    
    /**
     * 
     * @return type
     */
    public function isVisitor() {
        $subject = AAM_Backend_Subject::getInstance();
        
        return $subject->getUID() === AAM_Core_Subject_Visitor::UID;
    }
    
    /**
     * 
     * @return type
     */
    protected function retrieveAllRules() {
        $rules = AAM_Backend_Subject::getInstance()->getObject('uri')->getOption();
        
        $response = array(
            'recordsTotal'    => count($rules),
            'recordsFiltered' => count($rules),
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );
        
        foreach($rules as $id => $rule) {
            $response['data'][] = array(
                $id,
                $rule['uri'],
                $rule['type'],
                $rule['action'],
                'edit,delete'
            );
        }
        
        return $response;
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
        $object = AAM_Backend_Subject::getInstance()->getObject('uri');
        
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
            'uid'        => 'uri',
            'position'   => 55,
            'title'      => __('URI Access', AAM_KEY),
            'capability' => 'aam_manage_uri',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'core.settings.backendAccessControl,core.settings.frontendAccessControl',
            'view'       => __CLASS__
        ));
    }

}