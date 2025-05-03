<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core notification consol
 *
 * Track and display list of all warnings that has been detected during AAM
 * execution. The consol is used only when AAM interface was triggered in Admin side.
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Core_Console
{

    /**
     * List of Runtime errors related to AAM
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private static $_messages = array();

    /**
     * Add new warning
     *
     * @param string $message
     * @param string $args...
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function add($message)
    {
        //prepare search patterns
        $num    = func_num_args();
        $search = ($num > 1 ? array_fill(0, ($num - 1) * 2, null) : []);

        array_walk($search, function (&$value, $index) {
            $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
        });

        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }

        self::$_messages[] = preg_replace($search, $replace, $message, 1);
    }

    /**
     * Get list of all warnings
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_all()
    {
        return self::$_messages;
    }

    /**
     * Count the list of all notifications
     *
     * @return int
     * @access public
     *
     * @version 7.0.0
     */
    public static function count()
    {
        return count(self::$_messages);
    }

}