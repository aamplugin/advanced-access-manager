<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Api access manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Api_Manager {

    /**
     * Instance of itself
     * 
     * @var AAM_Api_Manager
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Map of routes and resources
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $resources = array(
        'post' => array (
            '/wp/v2/posts',
            '/wp/v2/posts/(?P<id>[\d]+)',
            '/wp/v2/pages',
            '/wp/v2/pages/(?P<id>[\d]+)',
            '/wp/v2/media',
            '/wp/v2/media/(?P<id>[\d]+)',
        ),
        'user' => array (
            '/wp/v2/users'
        ),
        'revision' => array (
            '/wp/v2/posts/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)',
            '/wp/v2/pages/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)'
        )
    );
    
    /**
     * Construct the manager
     * 
     * @return void
     * 
     * @access public
     */
    protected function __construct() {
        if (AAM_Core_Config::get('core.settings.apiAccessControl', true)) {
            // REST API action authorization. Triggered before call is dispatched
            add_filter(
                'rest_request_before_callbacks', array($this, 'beforeDispatch'), 10, 3
            );

            // Manage access to the RESTful endpoints
            add_filter('rest_pre_dispatch', array($this, 'authorizeRest'), 1, 3);

            // Register any additional endpoints with ConfigPress
            $additional = AAM_Core_Config::get('rest.manage.endpoint');

            if (!empty($additional) && is_array($additional)) {
                $this->resources = array_merge_recursive($this->resources, $additional);
            }
        }
    }
    
    /**
     * Authorize RESTful action before it is dispatched by RESTful Server
     * 
     * @param mixed  $response
     * @param object $handler
     * @param object $request
     * 
     * @return mixed
     * 
     * @access public
     */
    public function beforeDispatch($response, $handler, $request) {
        $result = null;
        
        foreach($this->resources as $res => $routes) {
            foreach($routes as $regex) {
                // Route to work with single post
                if(preg_match('#^' . $regex . '$#i', $request->get_route())) {
                    $classname = 'AAM_Api_Rest_Resource_' . ucfirst($res);
                    $result    = $classname::getInstance()->authorize($request);
                }
            }
        }
        
        return (is_null($result) ? $response : $result);
    }
    
    /**
     * Authorize REST request
     * 
     * Based on the matched route, check if it is disabled for current user
     * 
     * @param WP_Error|null   $response
     * @param WP_REST_Server  $server
     * @param WP_REST_Request $request
     * 
     * @return WP_Error|null
     * 
     * @access public
     */
    public function authorizeRest($response, $server, $request) {
        $user    = AAM::getUser();
        $object  = $user->getObject('route');
        $matched = $request->get_route();
        $method  = $request->get_method();
        
        foreach(array_keys($server->get_routes()) as $route) {
            if ($route === $matched || preg_match("#^{$route}$#i", $matched)) {
                if ($object->has('restful', $route, $method)) {
                    $response = new WP_Error(
                        'rest_access_denied', 
                        __('Access denied', AAM_KEY),
                        array('status' => 401)
                    );
                    break;
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Bootstrap the manager
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        global $wp;
        
        if (!empty($wp->query_vars['rest_route'])) {
            if (is_null(self::$_instance)) {
                self::$_instance = new self;
            }
        }
    }
    
}