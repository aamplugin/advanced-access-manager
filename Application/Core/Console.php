<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Consol Panel
 * 
 * Track and display list of all warnings that has been detected during AAM 
 * execution. The consol is used only when AAM interface was triggered in Admin side.
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Console {

    /**
     * List of Runtime errors related to AAM
     * 
     * @var array
     * 
     * @access private 
     * @static 
     */
    private static $_messages = array();

    /**
     * Add new warning
     * 
     * @param string $message
     * @param stirng $args...
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function add($message) {
        //prepare search patterns
        $num    = func_num_args();
        $search = ($num > 1 ? array_fill(0, ($num - 1) * 2, null) : array());
        
        array_walk($search, 'AAM_Core_Console::walk');
        
        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }
        
        self::$_messages[] = preg_replace(
                $search, $replace, __($message, AAM_KEY), 1
        );
    }

    /**
     * Get list of all warnings
     * 
     * @return array
     * 
     * @access public
     * @static
     */
    public static function getAll() {
        return self::$_messages;
    }
    
    /**
     * 
     * @return type
     */
    public static function count() {
        return count(self::$_messages);
    }
    
    /**
     * Replace place holders with markup
     * 
     * @param string $value
     * @param int    $index
     * 
     * @access protected
     * @static
     */
    protected static function walk(&$value, $index) {
        $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
    }

}