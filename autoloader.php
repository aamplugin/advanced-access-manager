<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Project autoloader
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Autoloader {

    /**
     * Class map
     * 
     * @var array
     * 
     * @access protected
     * @static 
     */
    protected static $classmap = array();

    /**
     * Add new index
     * 
     * @param string $classname
     * @param string $filepath
     * 
     * @access public
     * @static
     */
    public static function add($classname, $filepath) {
        self::$classmap[$classname] = $filepath;
    }
    
    /**
     * Autoloader for project Advanced Access Manager
     *
     * Try to load a class if prefix is AAM_
     *
     * @param string $classname
     */
    public static function load($classname) {
        if (array_key_exists($classname, self::$classmap)) {
            $filename = self::$classmap[$classname];
        } else {
            $chunks = explode('_', $classname);
            $prefix = array_shift($chunks);

            if ($prefix === 'AAM') {
                $base_path = dirname(__FILE__) . '/Application';
                $filename = $base_path . '/' . implode('/', $chunks) . '.php';
            }
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * Register autoloader
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        spl_autoload_register('AAM_Autoloader::load');
    }

}