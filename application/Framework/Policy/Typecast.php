<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM access policy type casting
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Policy_Typecast
{

    /**
     * Execute type casting
     *
     * @param string $expression
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public static function execute($expression)
    {
        $regex = '/^\(\*([\w]+)\)(.*)/i';

        // Note! It make no sense to have multiple type casting for one expression
        // due to the fact that they all would have to be concatenated as a string

        // If there is type casting, perform it
        if (preg_match($regex, $expression, $scale)) {
            $expression = self::_typecast($scale[2], $scale[1]);
        }

        return $expression;
    }


    /**
     * Cast value to specific type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     * @access protected
     *
     * @version 7.0.0
     */
    private static function _typecast($value, $type)
    {
        switch (strtolower($type)) {
            case 'string':
                $value = (string) $value;
                break;

            case 'ip':
                if (strpos($value, '/') !== false) {
                    $value = function($ip) use ($value) {
                        list ($subnet, $mask) = explode('/', $value);
                        $ipLong     = is_string($ip) ? ip2long($ip) : $ip;
                        $subnetLong = ip2long($subnet);
                        $maskLong   = -1 << (32 - $mask);

                        return ($subnetLong & $maskLong) === ($ipLong & $maskLong);
                    };
                } else {
                    $value = ip2long($value);
                }
                break;

            case 'int':
                $value = intval($value);
                break;

            case 'float':
                $value = floatval($value);
                break;

            case 'numeric':
                if (is_numeric($value)) {
                    $value = strpos($value, '.') !== false ? floatval($value) : intval($value);
                } else {
                    $value = 0;
                }
                break;

            case 'boolean':
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case 'array':
                $value = is_string($value) ? json_decode($value, true) : (array) $value;
                break;

            case 'null':
                $value = (in_array($value, [ '', 'null' ], true) ? null : $value);
                break;

            case 'date':
                try {
                    $value = new DateTime(
                        $value,
                        new DateTimeZone('UTC')
                    );
                } catch(Exception $e) {
                    $value = null;
                }
                break;

            default:
                $value = apply_filters('aam_marker_typecast_filter', $value, $type);
                break;
        }

        return $value;
    }

}