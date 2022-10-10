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
 *
 * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
 * @since 6.0.0 Initial implementation of the class
 *
 * @version 6.9.0
 */
class AAM_Autoloader
{

    /**
     * Class map
     *
     * @var array
     *
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0 Initial implementation of the property
     *
     * @access protected
     * @version 6.9.0
     *
     * @todo Remove in 7.0.0
     */
    protected static $class_map = array(
        'Firebase\JWT\JWT' => __DIR__ . '/firebase/JWT.php',
    );

    /**
     * Add new index
     *
     * @param string $class_name
     * @param string $file_path
     *
     * @access public
     * @version 6.0.0
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
     *
     * @access public
     * @version 6.0.0
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
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        spl_autoload_register('AAM_Autoloader::load');
    }

}