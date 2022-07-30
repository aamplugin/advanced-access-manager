<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Config
 *
 * @since 6.3.0 Added new method `replace`
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.0
 */
class AAM_Core_Config
{

    /**
     * Core AAM config db option
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_config';

    /**
     * Core config
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected static $config = array();

    /**
     * Load core AAM config
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function bootstrap()
    {
        self::$config = AAM_Core_API::getOption(self::DB_OPTION, array());
    }

    /**
     * Get config option
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function get($option, $default = null)
    {
        if (array_key_exists($option, self::$config)) {
            $response = self::$config[$option];
        } else {
            $response = self::readConfigPress($option, $default);
        }

        return ($response ? self::normalize($response) : $response);
    }

    /**
     * Normalize config option
     *
     * @param string $setting
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function normalize($setting)
    {
        return str_replace(array('{ABSPATH}'), array(ABSPATH), $setting);
    }

    /**
     * Set config option
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function set($option, $value)
    {
        self::$config[$option] = $value;

        // Save config to database
        return AAM_Core_API::updateOption(self::DB_OPTION, self::$config);
    }

    /**
     * Replace the entire config
     *
     * @param array $config
     *
     * @return boolean
     *
     * @access public
     * @version 6.3.0
     */
    public static function replace($config)
    {
        self::$config = $config;

        // Save config to database
        return AAM_Core_API::updateOption(self::DB_OPTION, self::$config);
    }

    /**
     * Delete config option
     *
     * @param string $option
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function delete($option)
    {
        if (array_key_exists($option, self::$config)) {
            unset(self::$config[$option]);

            $result = AAM_Core_API::updateOption(self::DB_OPTION, self::$config);
        }

        return !empty($result);
    }

    /**
     * Get ConfigPress parameter
     *
     * @param string $param
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    protected static function readConfigPress($param, $default = null)
    {
        $config = AAM_Core_ConfigPress::get('aam.' . $param, $default);

        if (is_array($config) && isset($config['userFunc'])) {
            if (is_callable($config['userFunc'])) {
                $response = call_user_func($config['userFunc']);
            } else {
                $response = $default;
            }
        } else {
            $response = $config;
        }

        return $response;
    }

    /**
     * Reset internal cache
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function reset()
    {
        self::$config = array();
    }

}