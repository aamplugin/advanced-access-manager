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
     * @return array|null
     *
     * @access public
     * @version 6.9.34
     */
    public function get_settings()
    {
        try {
            $result = $this->_get_settings_pointer();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set bulk of settings at once
     *
     * @param array $settings
     *
     * @return array
     *
     * @access public
     * @version 6.9.34
     */
    public function set_settings(array $settings)
    {
        try {
            $placement = &$this->_set_settings_pointer();
            $placement = $settings;

            if ($this->_save_option(self::DB_OPTION, $this->_settings)) {
                $result = $placement;
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
     * @version 6.9.34
     */
    public function get_setting($key, $default = null)
    {
        try {
            $value = $this->_get_settings_pointer();

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
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set setting
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function set_setting($key, $value)
    {
        try {
            $settings = &$this->_set_settings_pointer();

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
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Delete specific setting
     *
     * @param string $key
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function delete_setting($key)
    {
        try {
            $settings = &$this->_set_settings_pointer();
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

            $result = $this->_save_option(self::DB_OPTION, $this->_settings);

            if (!$result) {
                throw new RuntimeException('Failed to persist settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset/delete a single configuration
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function reset()
    {
        try {
            $result       = [];
            $access_level = $this->_get_access_level();
            $type         = is_null($access_level) ? null : $access_level::TYPE;

            if (is_null($type)) { // Return all settings
                $this->_settings = [];
            } elseif ($type === AAM_Framework_Type_AccessLevel::USER
                && isset($this->_settings[$type][$access_level->ID])
            ) {
                unset($this->_settings[$type][$access_level->ID]);
            } elseif ($type === AAM_Framework_Type_AccessLevel::ROLE
                && isset($this->_settings[$type][$access_level->name])
            ) {
                unset($this->_settings[$type][$access_level->name]);
            } elseif (isset($this->_settings[$type])) {
                unset($this->_settings[$type]);
            }

            if (!$this->_save_option(self::DB_OPTION, $this->_settings)) {
                throw new RuntimeException('Failed to persist configurations');
            }

        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get access settings pointer
     *
     * @return null|array
     *
     * @access private
     * @version 6.9.34
     */
    private function _get_settings_pointer()
    {
        $result       = null;
        $access_level = $this->_get_access_level();
        $type         = is_null($access_level) ? null : $access_level::TYPE;

        if (is_null($type)) { // Return all settings
            $result = $this->_settings;
        } elseif ($type === AAM_Framework_Type_AccessLevel::USER
            && isset($this->_settings[$type][$access_level->ID])
        ){
            $result = $this->_settings[$type][$access_level->ID];
        } elseif ($type === AAM_Framework_Type_AccessLevel::ROLE
            && isset($this->_settings[$type][$access_level->name])
        ){
            $result = $this->_settings[$type][$access_level->name];
        } elseif (isset($this->_settings[$type])) {
            $result = $this->_settings[$type];
        }

        return $result;
    }

    /**
     * Set access settings pointer
     *
     * @return &array|null
     *
     * @access private
     * @version 6.9.34
     */
    private function &_set_settings_pointer()
    {
        $result       = null;
        $access_level = $this->_get_access_level();
        $type         = is_null($access_level) ? null : $access_level::TYPE;

        if (is_null($type)) { // Return all settings
            $result = &$this->_settings;
        } elseif (in_array($type, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::ROLE
        ], true)) { // User & Role access levels have additional level
            if (!isset($this->_settings[$type])) {
                $this->_settings[$type] = [];
            }

            if ($type === AAM_Framework_Type_AccessLevel::USER) {
                $level_id = $access_level->ID;
            } else {
                $level_id = $access_level->name;
            }

            if (!isset($this->_settings[$type][$level_id])) {
                $this->_settings[$type][$level_id] = [];
            }

            $result = &$this->_settings[$type][$level_id];
        } else {
            if (!isset($this->_settings[$type])) {
                $this->_settings[$type] = [];
            }

            $result = &$this->_settings[$type];
        }

        return $result;
    }

}