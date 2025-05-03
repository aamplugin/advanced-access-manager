<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM access policy xpath evaluator
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Policy_Xpath
{

    /**
     * Get value by xpath
     *
     * This method supports multiple different path
     *
     * @param mixed        $source
     * @param string|array $xpath
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    public static function get_value_by_xpath($source, $xpath)
    {
        $value = $source;

        // Do we need to parse the xpath? It is possible that the xpath was already
        // parsed
        $parsed = is_array($xpath) ? $xpath : self::parse_xpath($xpath);

        foreach($parsed as $l) {
            if (is_object($value)) {
                if (isset($value->{$l})) {
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

    /**
     * Parse xpath string into array
     *
     * @param string $xpath
     *
     * @return array
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function parse_xpath($xpath)
    {
        $result = trim(
            str_replace(
                array('["', '[', '"]', ']', '..'), '.', $xpath
            ),
            ' .' // white space is important!
        );

        return explode('.', $result);
    }

}