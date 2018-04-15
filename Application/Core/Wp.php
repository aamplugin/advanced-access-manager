<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM WordPress core hooks
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Wp {
    
    /**
     * Initialize core hooks
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        // Disable XML-RPC if needed
        if (!AAM_Core_Config::get('core.xmlrpc', true)) {
            add_filter('xmlrpc_enabled', '__return_false');
        }
        
        // Disable RESTfull API if needed
        if (!AAM_Core_Config::get('core.restfull', true)) {
            add_filter(
                    'rest_authentication_errors', 'AAM_Core_Wp::disableRestful', 1
            );
        }
        
        // Manage access to the RESTful endpoints
        add_filter('rest_pre_dispatch', 'AAM_Core_Wp::restAuth', 1, 3);
    }
    
    /**
     * 
     * @param WP_Error|null|bool $response
     * 
     * @return \WP_Error
     */
    public static function disableRestful($response) {
        if (!is_wp_error($response)) {
            $response = new WP_Error(
                'access_denied', 
                __('RESTfull API is disabled', AAM_KEY),
                array('status' => 403)
            );
        }
        
        return $response;
    }
    
    /**
     * 
     * @param WP_Error $response
     * @param type $server
     * @param type $request
     * @return \WP_Error
     */
    public static function restAuth($response, $server, $request) {
        $user    = AAM::getUser();
        $object  = $user->getObject('route');
        $matched = $request->get_route();
        $method  = $request->get_method();
        
        foreach(array_keys($server->get_routes()) as $route) {
            if ($route == $matched || preg_match("#^{$route}$#i", $matched)) {
                if ($object->has('restful', $route, $method)) {
                    $response = new WP_Error(
                        'access_denied', 
                        __('Access denied', AAM_KEY),
                        array('status' => 401)
                    );
                    break;
                }
            }
        }
        
        return $response;
    }
}