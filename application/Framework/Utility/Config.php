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
class AAM_Framework_Utility_Config
{

    /**
     * Core AAM config db option
     *
     * @version 6.9.34
     */
    const DB_OPTION = 'aam_config';

    /**
     * ConfigPress db option
     *
     * @version 6.9.34
     */
    const DB_CONFIGPRESS_OPTION = 'aam_configpress';

    /**
     * Collection of configurations
     *
     * @var array
     *
     * @access protected
     * @version 6.9.34
     */
    private $_configs = [];

    /**
     * ConfigPress raw INI
     *
     * @var string
     *
     * @access private
     * @version 6.9.34
     */
    private $_configpress = '';

    /**
     * Load the configuration from DB
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        $this->_configs     = $this->_read_option(self::DB_OPTION, []);
        $this->_configpress = $this->_read_option(self::DB_CONFIGPRESS_OPTION, '');

        // Parse ConfigPress options & merge them with config
        $parsed = $this->_parse_configpress($this->_configpress);

        if (!empty($parsed)) {
            $this->_configs = array_merge($this->_configs, $parsed);
        }
    }

    /**
     * Return list of all explicitly defined configurations
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_configs()
    {
        try {
            $result = $this->_configs;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set bulk of configurations at once
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function set_configs(array $configs)
    {
        try {
            $this->_configs = $configs;

            if ($this->_save_option(self::DB_OPTION, $this->_configs)) {
                $result = $configs;
            } else {
                throw new RuntimeException('Failed to persist configurations');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get configuration
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_config($key, $default = null)
    {
        try {
            if (array_key_exists($key, $this->_configs)) {
                $result = $this->_configs[$key];
            } else {
                $result = apply_filters('aam_get_config_filter', $default, $key);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get configuration - alias for the get_config method
     *
     * @param string $key
     * @param mixed  $default [optional]
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function config($key, $default = null)
    {
        return $this->get_config($key, $default);
    }

    /**
     * Set configuration
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_config($key, $value)
    {
        try {
            $this->_configs[$key] = $value;

            if ($this->_save_option(self::DB_OPTION, $this->_configs)) {
                $result = true;
            } else {
                throw new RuntimeException('Failed to persist configurations');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset/delete a single configuration
     *
     * @param string $key
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function reset_config($key)
    {
        try {
            if (array_key_exists($key, $this->_configs)) {
                unset($this->_configs[$key]);
            }

            if ($this->_save_option(self::DB_OPTION, $this->_configs)) {
                $result = true;
            } else {
                throw new RuntimeException('Failed to persist configurations');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get ConfigPress raw INI
     *
     * @return string|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_configpress()
    {
        try {
            $result = $this->_configpress;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set/save ConfigPress INI
     *
     * @param string $ini
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function set_configpress($ini)
    {
        try {
            // Validate the provided INI
            $parsed = $this->_parse_configpress($ini);

            if (is_array($parsed)) {
                $result = $this->_save_option(self::DB_CONFIGPRESS_OPTION, $ini);
            } else {
                $result = false;
            }

            if ($result) {
                $this->_configpress = $ini;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset ConfigPress
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    public function reset_configpress()
    {
        try {
            $this->_configpress = '';

            // Ignore result because if you are trying to delete the same option
            // twice, the second attempt will return false as the option is no longer
            // in the DB
            $this->_delete_option(self::DB_CONFIGPRESS_OPTION);

            $result = $this->_configpress;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset configurations
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function reset()
    {
        try {
            $this->_configs = [];

            // Ignore result because if you are trying to delete the same option
            // twice, the second attempt will return false as the option is no longer
            // in the DB
            $result = $this->_delete_option(self::DB_OPTION);
            $result = $result && $this->_delete_option(self::DB_CONFIGPRESS_OPTION);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Parse INI config
     *
     * @param string $ini
     *
     * @return array
     * @version 7.0.0
     */
    private function _parse_configpress($ini)
    {
        $result = [];

        if (!empty($ini) && is_string($ini)) {
            // Parse the string & handle any warnings or errors properly
            set_error_handler(function($_, $message) {
                $this->_handle_error(new InvalidArgumentException($message));
            });

            $result = parse_ini_string($ini, true, INI_SCANNER_TYPED);

            restore_error_handler();

            if ($result !== false) { // Clear error
                // If we have "aam" key, then AAM ConfigPress is properly formatted
                // and we take all the values from this section.
                //
                // Otherwise - assume that user forgot to add the "[aam]" section
                if (array_key_exists('aam', $result)) {
                    $result = $result['aam'];
                }
            }
        }

        return $result;
    }

}