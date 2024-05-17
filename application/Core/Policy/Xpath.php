<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core xpath evaluator
 *
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/365
 * @since 6.9.17 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Core_Policy_Xpath
{

    /**
     * Get value by xpath
     *
     * This method supports multiple different path
     *
     * @param mixed  $obj
     * @param string $xpath
     *
     * @return mixed
     *
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/365
     * @since 6.9.17 Initial implementation of the method
     *
     * @access private
     * @version 6.9.28
     */
    public static function get_value_by_xpath($obj, $xpath)
    {
        $value = $obj;
        $path  = trim(
            str_replace(
                array('["', '[', '"]', ']', '..'), '.', $xpath
            ),
            ' .' // white space is important!
        );

        foreach(explode('.', $path) as $l) {
            if (is_object($value)) {
                if (property_exists($value, $l)) {
                    $value = $value->{$l};
                } elseif (method_exists($value, $l)) {
                    $value = $value->$l();
                } else {
                    $value = null;
                    break;
                }
            } else if (is_array($value)) {
                if (array_key_exists($l, $value)) {
                    $value = $value[$l];
                } else {
                    $value = null;
                    break;
                }
            }
        }

        return $value;
    }

}