<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Configurations utility
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_Config implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Core AAM config db option
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_config';

    /**
     * Collection of configurations
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_configs = null;

    /**
     * @inheritDoc
     */
    protected function __construct()
    {
        $this->_configs = apply_filters(
            'aam_initialize_config', $this->_read_config()
        );
    }

    /**
     * Get all configurations or a very specific one
     *
     * @param string $name    [Optional]
     * @param mixed  $default [Optional]
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get($name = null, $default = null)
    {
        if (!empty($name)) {
            if (array_key_exists($name, $this->_configs)) {
                $result = $this->_configs[$name];
            } else {
                $result = apply_filters('aam_get_config_filter', $default, $name);
            }
        } else {
            $result = $this->_configs;
        }

        return $result;
    }

    /**
     * Set either a very specific config or all of them at once
     *
     * @param string|array $config
     * @param mixed        $value  [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set($config, $value = null)
    {
        if (is_array($config)) {
            $this->_configs = $config;
        } elseif (is_string($config)) {
            $this->_configs[$config] = $value;
        } else {
            throw new InvalidArgumentException('The config is invalid');
        }

        // Saving the configurations in DB
        return $this->_save_config();
    }

    /**
     * Reset/delete a single configuration or all at once
     *
     * @param string $name [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($name = null)
    {
        if (is_string($name)) {
            if (array_key_exists($name, $this->_configs)) {
                unset($this->_configs[$name]);
            }
        } else {
            $this->_configs = [];
        }

        return $this->_save_config();
    }

    /**
     * Read configurations from DB
     *
     * @param mixed $default [Optional]
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _read_config($default = [])
    {
        if (is_multisite()) {
            $result = get_blog_option(
                get_current_blog_id(), self::DB_OPTION, $default
            );
        } else {
            $result = get_option(self::DB_OPTION, $default);
        }

        return $result;
    }

    /**
     * Save configurations to DB
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _save_config()
    {
        // Saving the configurations in DB
        $old_value = $this->_read_config(null);

        if ($old_value === null) { // Option does not exist, add it
            if (is_multisite()) {
                $result = add_blog_option(
                    get_current_blog_id(), self::DB_OPTION, $this->_configs
                );
            } else {
                $result = add_option(self::DB_OPTION, $this->_configs, '', true);
            }
        } elseif (maybe_serialize($old_value) !== maybe_serialize($this->_configs)) {
            if (is_multisite()) {
                $result = update_blog_option(
                    get_current_blog_id(), self::DB_OPTION, $this->_configs
                );
            } else {
                $result = update_option(self::DB_OPTION, $this->_configs, true);
            }
        } else{
            $result = true;
        }

        return $result;
    }

}