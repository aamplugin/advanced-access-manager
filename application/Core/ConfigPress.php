<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ConfigPress layer
 *
 * @since 6.7.4 https://github.com/aamplugin/advanced-access-manager/issues/160
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.4
 */
final class AAM_Core_ConfigPress
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * DB option name
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_configpress';

    /**
     * Parsed config
     *
     * @var array
     *
     * @access protected
     * @version 6.0.0
     */
    protected $config = null;

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
        try {
            $reader       = new AAM_Core_ConfigPress_Reader;
            $this->config = $reader->parseString($this->read());
        } catch (Exception $e) {
            AAM_Core_Console::add($e->getMessage());
            $this->config = array();
        }
    }

    /**
     * Read config from the database
     *
     * @return string
     *
     * @since 6.7.4 https://github.com/aamplugin/advanced-access-manager/issues/160
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.4
     */
    public function read()
    {
        $config = AAM_Core_API::getOption(self::DB_OPTION, null);

        return (empty($config) ? '[aam]' : $config);
    }

    /**
     * Save config to the database
     *
     * @param string $value
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function save($value)
    {
        return AAM_Core_API::updateOption(self::DB_OPTION, $value);
    }

    /**
     * Get configuration option/setting
     *
     * If $option is defined, return it, otherwise return the $default value
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public static function get($option = null, $default = null)
    {
        //init config only when requested and only one time
        $instance = self::getInstance();

        if (is_null($option)) {
            $value = $instance->config;
        } else {
            $chunks = explode('.', $option);
            $value = $instance->config;
            foreach ($chunks as $chunk) {
                if (isset($value[$chunk])) {
                    $value = $value[$chunk];
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

}