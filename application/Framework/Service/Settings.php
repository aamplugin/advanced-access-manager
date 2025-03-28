<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Settings service
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Framework_Service_Settings
{

    use AAM_Framework_Service_DbTrait,
        AAM_Framework_Service_BaseTrait;

    /**
     * Core AAM config db option
     *
     * @version 6.9.34
     */
    const DB_OPTION = 'aam_access_settings';

    /**
     * Collection of settings
     *
     * @var array
     *
     * @access protected
     * @version 6.9.34
     */
    private $_settings = [];

    /**
     * Load the settings from DB
     *
     * @return void
     *
     * @access protected
     * @version 6.9.34
     */
    protected function initialize_hooks()
    {
        $this->_settings = $this->_read_option(self::DB_OPTION, []);
    }

    /**
     * Return list of all explicitly defined settings
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.34
     */
    public function get_settings($inline_context = null)
    {
        try {
            $result = $this->_get_settings_pointer($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set bulk of settings at once
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.34
     */
    public function set_settings(array $settings, $inline_context = null)
    {
        try {
            $placement = &$this->_set_settings_pointer($inline_context);
            $placement = $settings;

            if ($this->_save_option(self::DB_OPTION, $this->_settings)) {
                $result = $placement;
            } else {
                throw new RuntimeException('Failed to persist configurations');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get configuration
     *
     * @param string $key
     * @param mixed  $default
     * @param array  $inline_context
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.34
     */
    public function get_setting($key, $default = null, $inline_context = null)
    {
        try {
            $value = $this->_get_settings_pointer($inline_context);

            foreach (explode('.', $key) as $ns) {
                if (isset($value[$ns])) {
                    $value = $value[$ns];
                } else {
                    $value = null;
                    break;
                }
            }

            $result = (is_null($value) ? $default : $value);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set setting
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function set_setting($key, $value, $inline_context = null)
    {
        try {
            $settings = &$this->_set_settings_pointer($inline_context);

            foreach (explode('.', $key) as $ns) {
                if (!isset($settings[$ns])) {
                    $settings[$ns] = array();
                }
                $settings = &$settings[$ns];
            }

            $settings = $value;

            if ($this->_save_option(self::DB_OPTION, $this->_settings)) {
                $result = true;
            } else {
                throw new RuntimeException('Failed to persist settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete specific setting
     *
     * @param string $key
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function delete_setting($key, $inline_context = null)
    {
        try {
            $settings = &$this->_set_settings_pointer($inline_context);
            $path     = explode('.', $key);

            for($i = 0; $i < count($path); $i++) {
                if (!isset($settings[$path[$i]])) {
                    break;
                } elseif ($i + 1 === count($path)) { // The last one?
                    unset($settings[$path[$i]]);
                } else {
                    $settings = &$settings[$path[$i]];
                }
            }

            if ($this->_save_option(self::DB_OPTION, $this->_settings)) {
                $result = $this->_get_settings_pointer($inline_context);
            } else {
                throw new RuntimeException('Failed to persist settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset/delete a single configuration
     *
     * @param array $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function reset($inline_context = null)
    {
        try {
            $result       = [];
            $access_level = $this->_get_subject($inline_context);
            $type         = is_null($access_level) ? null : $access_level::UID;

            if (is_null($type)) { // Reset all settings
                $this->_settings = [];

                AAM_Core_API::clearSettings();

            } elseif (in_array($type, [
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ROLE
            ], true) && isset($this->_settings[$type][$access_level->getId()])) {
                unset($this->_settings[$type][$access_level->getId()]);
            } elseif (isset($this->_settings[$type])) {
                unset($this->_settings[$type]);
            }

            if (!$this->_save_option(self::DB_OPTION, $this->_settings)) {
                throw new RuntimeException('Failed to persist configurations');
            }

        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get access settings pointer
     *
     * @param array $inline_context
     *
     * @return null|array
     *
     * @access private
     * @version 6.9.34
     */
    private function _get_settings_pointer($inline_context)
    {
        $result       = null;
        $access_level = $this->_get_subject($inline_context);
        $type         = is_null($access_level) ? null : $access_level::UID;

        if (is_null($type)) { // Return all settings
            $result = $this->_settings;
        } elseif (in_array($type, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::ROLE
        ], true) && isset($this->_settings[$type][$access_level->getId()])) {
            $result = $this->_settings[$type][$access_level->getId()];
        } elseif (isset($this->_settings[$type])) {
            $result = $this->_settings[$type];
        }

        return $result;
    }

    /**
     * Set access settings pointer
     *
     * @param array $inline_context
     *
     * @return &array|null
     *
     * @access private
     * @version 6.9.34
     */
    private function &_set_settings_pointer($inline_context)
    {
        $result       = null;
        $access_level = $this->_get_subject($inline_context);
        $type         = is_null($access_level) ? null : $access_level::UID;

        if (is_null($type)) { // Return all settings
            $result = &$this->_settings;
        } elseif (in_array($type, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::ROLE
        ], true)) { // User & Role access levels have additional level
            if (!isset($this->_settings[$type])) {
                $this->_settings[$type] = [];
            }

            if (!isset($this->_settings[$type][$access_level->getId()])) {
                $this->_settings[$type][$access_level->getId()] = [];
            }

            $result = &$this->_settings[$type][$access_level->getId()];
        } else {
            if (!isset($this->_settings[$type])) {
                $this->_settings[$type] = [];
            }

            $result = &$this->_settings[$type];
        }

        return $result;
    }

}