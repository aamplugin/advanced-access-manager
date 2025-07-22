<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Project auto-loader
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Autoloader
{

    /**
     * PRS HTTP Message package
     *
     * @version 7.0.0
     */
    const PSRHM_BASEDIR = __DIR__ . '/vendor/psr-http-message';

    /**
     * Whip package
     *
     * @version 7.0.0
     */
    const WHIP_BASEDIR = __DIR__ . '/vendor/whip';

    /**
     * Class map
     *
     * @var array
     * @access protected
     *
     * @version 7.0.7
     */
    protected static $class_map = [];

    /**
     * Add new index
     *
     * @param string $class_name
     * @param string $file_path
     * @access public
     *
     * @version 7.0.0
     */
    public static function add($class_name, $file_path)
    {
        self::$class_map[$class_name] = $file_path;
    }

    /**
     * Auto-loader for project Advanced Access Manager
     *
     * Try to load a class if prefix is AAM_
     *
     * @param string $class_name
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function load($class_name)
    {
        if (array_key_exists($class_name, self::$class_map)) {
            $filename = self::$class_map[$class_name];
        } else {
            $chunks = explode('_', $class_name);
            $prefix = array_shift($chunks);

            if ($prefix === 'AAM') {
                $base_path = __DIR__ . '/application';
                $filename  = $base_path . '/' . implode('/', $chunks) . '.php';
            }
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * Register auto-loader
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        spl_autoload_register('AAM_Autoloader::load');
    }

}