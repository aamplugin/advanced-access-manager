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
     * @access protected
     * @version 6.2.1
     */
    private static function _typecast($value, $type)
    {
        switch (strtolower($type)) {
            case 'string':
                $value = (string) $value;
                break;

            case 'ip':
                $value = inet_pton($value);
                break;

            case 'int':
                $value = (int) $value;
                break;

            case 'boolean':
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case 'array':
                $value = json_decode($value, true);
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