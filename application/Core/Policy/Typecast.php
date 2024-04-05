<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core policy type casting
 *
 * @package AAM
 * @version 6.2.1
 */
class AAM_Core_Policy_Typecast
{

    /**
     * Support types
     *
     * @version 6.2.1
     */
    const SUPPORTED_TYPES = 'string|ip|int|boolean|bool|array|null|date';

    /**
     * Execute type casting
     *
     * @param string $expression
     *
     * @return mixed
     *
     * @access public
     * @version 6.2.1
     */
    public static function execute($expression)
    {
        $regex = '/^\(\*(' . self::SUPPORTED_TYPES . ')\)(.*)/i';

        // Note! It make no sense to have multiple type casting for one expression
        // due to the fact that they all would have to be concatenated as a string

        // If there is type casting, perform it
        if (preg_match( $regex, $expression, $scale)) {
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
     *
     * @since 6.9.24 https://github.com/aamplugin/advanced-access-manager/issues/349
     * @since 6.2.1  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.24
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
                    $value = is_float($value) ? floatval($value) : intval($value);
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
                $value = ($value === '' ? null : $value);
                break;

            case 'date':
                try {
                    $value = new DateTime(
                        $value,
                        new DateTimeZone('UTC')
                    );
                } catch(Exception $e) {
                    _doing_it_wrong(
                        __CLASS__ . '::' . __METHOD__,
                        'Cannot typecast value to DateTime',
                        AAM_VERSION
                    );
                    $value = null;
                }
                break;

            default:
                $value = apply_filters('aam_token_typecast_filter', $value, $type);
                break;
        }

        return $value;
    }

}