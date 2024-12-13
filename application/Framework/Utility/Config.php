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
        return AAM_Framework_Manager::_()->db->read(self::DB_OPTION, $default);
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
        return AAM_Framework_Manager::_()->db->write(
            self::DB_OPTION, $this->_configs
        );
    }

}