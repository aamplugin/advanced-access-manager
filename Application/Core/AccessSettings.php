<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * AAM Access Settings repository
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_AccessSettings
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * AAM access settings option
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_access_settings';

    /**
     * Full repository of the settings
     *
     * @var array
     *
     * @access private
     * @version 6.0.0
     */
    private $_settings = array();

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        $this->_settings = AAM_Core_API::getOption(self::DB_OPTION, array());
    }

    /**
     * Get access settings
     *
     * @param string $option
     * @param array  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function get($option, $default = array())
    {
        $value = $this->_settings;

        foreach (explode('.', $option) as $ns) {
            if (isset($value[$ns])) {
                $value = $value[$ns];
            } else {
                $value = null;
                break;
            }
        }

        return (is_null($value) ? $default : $value);
    }

    /**
     * Set access settings
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return AAM_Core_AccessSettings
     *
     * @access public
     * @version 6.0.0
     */
    public function set($option, $value)
    {
        $settings = &$this->_settings;

        foreach (explode('.', $option) as $ns) {
            if (!isset($settings[$ns])) {
                $settings[$ns] = array();
            }
            $settings = &$settings[$ns];
        }

        $settings = $value;

        return $this;
    }

    /**
     * Unset specified access settings
     *
     * @param string $option
     *
     * @return AAM_Core_AccessSettings
     *
     * @access public
     * @version 6.0.0
     */
    public function delete($option)
    {
        $settings = &$this->_settings;
        $path     = explode('.', $option);

        for($i = 0; $i < count($path); $i++) {
            if (!isset($settings[$path[$i]])) {
                break;
            } elseif ($i + 1 === count($path)) {
                unset($settings[$path[$i]]);
            } else {
                $settings = &$settings[$path[$i]];
            }
        }

        return $this;
    }

    /**
     * Save access settings
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        return AAM_Core_API::updateOption(self::DB_OPTION, $this->_settings);
    }

    /**
     * Reset all the settings
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function reset()
    {
        $this->_settings = array();

        return $this->save();
    }

}