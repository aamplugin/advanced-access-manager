<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * HTTP request layer
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Request
{

    /**
     * Get parameter from global _GET array
     *
     * @param string $param   GET Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function get($param = null, $default = null)
    {
        return self::readArray($_GET, $param, $default);
    }

    /**
     * Get parameter from global _POST array
     *
     * @param string $param   POST Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function post($param = null, $default = null)
    {
        return self::readArray($_POST, $param, $default);
    }

    /**
     * Get parameter from global _REQUEST array
     *
     * @param string $param   REQUEST Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function request($param = null, $default = null)
    {
        return self::readArray($_REQUEST, $param, $default);
    }

    /**
     * Get parameter from global _SERVER array
     *
     * @param string $param   SERVER Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function server($param = null, $default = null)
    {
        return self::readArray($_SERVER, $param, $default);
    }

    /**
     * Get parameter from global _COOKIE array
     *
     * @param string $param   _COOKIE Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function cookie($param = null, $default = null)
    {
        return self::readArray($_COOKIE, $param, $default);
    }

    /**
     * Check array for specified parameter and return the it's value or
     * default one
     *
     * @param array  $array   Global array _GET, _POST etc
     * @param string $param   Array Parameter
     * @param mixed  $default Default value
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function readArray($array, $param, $default)
    {
        $value = $default;
        if (is_null($param)) {
            $value = $array;
        } else {
            $chunks = explode('.', $param);
            $value = $array;
            foreach ($chunks as $chunk) {
                if (isset($value[$chunk])) {
                    $value = $value[$chunk];
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

}