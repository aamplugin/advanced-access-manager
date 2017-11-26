<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ConfigPress layer
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @todo Deprecated - Remove in May 2018
 */
final class AAM_Core_ConfigPress {

    /**
     * Get ConfigPress parameter
     * 
     * @param string $param
     * @param mixed $default
     * 
     * @return mixed
     * 
     * @access public
     * @static
     */
    public static function get($param, $default = null) {
        if (class_exists('ConfigPress')) {
            $response = ConfigPress::get($param, $default);
        } else {
            $response = $default;
        }

        return self::parseParam($response, $default);
    }

    /**
     * Parse found parameter
     * 
     * @param mixed $param
     * @param mixed $default
     * 
     * @return mixed
     * 
     * @access protected
     * @static
     */
    protected static function parseParam($param, $default) {
        if (is_array($param) && isset($param['userFunc'])) {
            if (is_callable($param['userFunc'])) {
                $response = call_user_func($param['userFunc']);
            } else {
                $response = $default;
            }
        } else {
            $response = $param;
        }

        return $response;
    }

}