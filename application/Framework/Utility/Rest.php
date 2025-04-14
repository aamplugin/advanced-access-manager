<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Rest implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Security register new RESTful API endpoint
     *
     * Note! Do not use this way to register RESTful API endpoint if your permissions
     * check function relies on request params.
     *
     * @param string       $ns    Route namespace
     * @param string       $route Route
     * @param array        $args  Route schema
     * @param string|array $auth  Permissions callback
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function register($ns, $route, $args, $auth = null)
    {
        $register = true;
        $cb       = null;

        // Step #1. Prepare RESTful API endpoint schema
        $args = apply_filters('aam_rest_route_args_filter', $args, $route, $ns);

        // Step #2. Let's determine if we even want to register API endpoint
        if (is_string($auth)) {
            $register = current_user_can($auth);
            $cb       = function() use ($auth) { return current_user_can($auth); };
        } elseif (is_array($auth)) {
            $register = array_reduce($auth, function($r, $c) {
                return $r && current_user_can($c);
            }, $register);

            $cb = function() use ($auth) {
                return array_reduce($auth, function($r, $c) {
                    return $r && current_user_can($c);
                }, true);
            };
        } elseif (is_a($auth, Closure::class)) {
            $cb       = $auth;
            $register = call_user_func($auth);
        }

        if ((defined('AAM_FORCE_REST_API_REGISTER')
            && constant('AAM_FORCE_REST_API_REGISTER')) || $register
        ) {
            $args['permission_callback'] = $cb; // Adding Permissions check cb
            $result                      = register_rest_route($ns, $route, $args);
        } else {
            $result = false;
        }

        return $result;
    }

}