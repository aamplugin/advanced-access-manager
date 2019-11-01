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
 * Backend view helper
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_View_Helper
{

    /**
     * Prepare phrase or label
     *
     * @param string $phrase
     * @param mixed  $...
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    public static function preparePhrase($phrase)
    {
        // Prepare search patterns
        $num    = func_num_args();
        $search = ($num > 1 ? array_fill(0, ($num - 1) * 2, null) : array());

        array_walk($search, 'AAM_Backend_View_Helper::prepareWalk');

        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }

        // Localize the phase first
        return preg_replace($search, $replace, __($phrase, AAM_KEY), 1);
    }

    /**
     * Prepare the wrapper replacement
     *
     * @param string $value
     * @param int    $index
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function prepareWalk(&$value, $index)
    {
        $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
    }

}