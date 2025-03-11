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
 * @version 7.0.0
 */
class AAM_Framework_Service_Settings
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Core AAM config db option
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_settings';

    /**
     * Collection of settings
     *
     * @var array
     * @access protected
     *
     * @version 7.0.0
     */
    private $_data = [];

    /**
     * Load the settings from DB
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        $this->_data = apply_filters(
            'aam_init_access_level_settings_filter',
            $this->db->read(self::DB_OPTION, []),
            $this
        );
    }

    /**
     * Return list of all explicitly defined settings
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
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
     * @access public
     *
     * @version 7.0.0
     */
    public function set_settings(array $settings)
    {
        try {
            $placement = &$this->_set_settings_pointer();
            $placement = $settings;

            if ($this->db->write(self::DB_OPTION, $this->_data)) {
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
     * @access public
     *
     * @version 7.0.0
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
     * @access public
     *
     * @version 7.0.0
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

            if ($this->db->write(self::DB_OPTION, $this->_data)) {
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
     * @access public
     *
     * @version 7.0.0
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

            $result = $this->db->write(self::DB_OPTION, $this->_data);

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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            $access_level = $this->_get_access_level();
            $type         = $access_level->type;
            $id           = $access_level->get_id();

            if (in_array(
                $type,
                [
                    AAM_Framework_Type_AccessLevel::USER,
                    AAM_Framework_Type_AccessLevel::ROLE
                ],
                true) && isset($this->_data[$type][$id])
            ) {
                unset($this->_data[$type][$id]);
            } elseif (isset($this->_data[$type])) {
                unset($this->_data[$type]);
            }

            $result = $this->db->write(self::DB_OPTION, $this->_data);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get access settings pointer
     *
     * @return null|array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_settings_pointer()
    {
        $result       = null;
        $access_level = $this->_get_access_level();
        $type         = $access_level->type;
        $id           = $access_level->get_id();

        if (in_array(
            $type,
            [
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ROLE
            ],
            true) && isset($this->_data[$type][$id])
        ) {
            $result = $this->_data[$type][$id];
        } elseif (isset($this->_data[$type])) {
            $result = $this->_data[$type];
        }

        return $result;
    }

    /**
     * Set access settings pointer
     *
     * @return &array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function &_set_settings_pointer()
    {
        $result       = null;
        $access_level = $this->_get_access_level();
        $type         = $access_level->type;
        $id           = $access_level->get_id();

        if (in_array($type, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::ROLE
        ], true)) { // User & Role access levels have additional level
            if (!isset($this->_data[$type])) {
                $this->_data[$type] = [];
            }

            if (!isset($this->_data[$type][$id])) {
                $this->_data[$type][$id] = [];
            }

            $result = &$this->_data[$type][$id];
        } else {
            if (!isset($this->_data[$type])) {
                $this->_data[$type] = [];
            }

            $result = &$this->_data[$type];
        }

        return $result;
    }

}