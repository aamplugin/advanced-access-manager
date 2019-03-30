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
class AAM_Backend_Feature_Main_Route extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_api_routes')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_api_routes'));
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getTable() {
        $response = array('data' => $this->retrieveAllRoutes());

        return wp_json_encode($response);
    }

    /**
     * 
     * @return type
     */
    public function save() {
       $type   = filter_input(INPUT_POST, 'type');
       $route  = filter_input(INPUT_POST, 'route');
       $method = filter_input(INPUT_POST, 'method');
       $value  = filter_input(INPUT_POST, 'value');

       $object = AAM_Backend_Subject::getInstance()->getObject('route');

       $object->save($type, $route, $method, $value);

       return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('route');
    }

    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/route.phtml';
    }
    
    /**
     * 
     * @return type
     */
    protected function retrieveAllRoutes() {
        $response = array();
        $object   = AAM_Backend_Subject::getInstance()->getObject('route');
        
        //build all RESTful routes
        if (AAM::api()->getConfig('core.settings.restful', true)) {
            foreach (rest_get_server()->get_routes() as $route => $handlers) {
                $methods = array();
                foreach($handlers as $handler) {
                    $methods = array_merge($methods, array_keys($handler['methods']));
                }

                foreach(array_unique($methods) as $method) {
                    $response[] = array(
                        $route,
                        'restful',
                        $method,
                        htmlspecialchars($route),
                        $object->has('restful', $route, $method) ? 'checked' : 'unchecked'
                    );
                }
            }
        }
        
        // Build XML RPC routes
        if (AAM::api()->getConfig('core.settings.xmlrpc', true)) {
            foreach(array_keys(AAM_Core_API::getXMLRPCServer()->methods) as $route) {
                $response[] = array(
                    $route,
                    'xmlrpc',
                    'POST',
                    htmlspecialchars($route),
                    $object->has('xmlrpc', $route) ? 'checked' : 'unchecked'
                );
            }
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
        $object = AAM_Backend_Subject::getInstance()->getObject('route');
        
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
            'uid'        => 'route',
            'position'   => 50,
            'title'      => __('API Routes', AAM_KEY),
            'capability' => 'aam_manage_api_routes',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'option'     => 'core.settings.apiAccessControl',
            'view'       => __CLASS__
        ));
    }

}