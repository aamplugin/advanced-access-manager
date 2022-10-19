<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ConfigPress Reader
 *
 * Parse configuration string
 *
 * @since 6.9.1 https://github.com/aamplugin/advanced-access-manager/issues/226
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.1
 */
class AAM_Core_ConfigPress_Reader
{

    /**
     * Default param separator
     *
     * @version 6.0.0
     */
    const SEPARATOR = '.';

    /**
     * Parse INI config
     *
     * Parse configuration string
     *
     * @param  string $string
     *
     * @return array|bool
     *
     * @throws Exception
     * @version 6.0.0
     */
    public function parseString($string)
    {
        if (!empty($string)) {
            //parse the string
            set_error_handler(array($this, 'parserError'));
            $ini = parse_ini_string($string, true);
            restore_error_handler();

            $response = $this->process(is_array($ini) ? $ini : array());
        } else {
            $response = array();
        }

        return $response;
    }

    /**
     * Add error to the AAM console
     *
     * @param string $error
     * @param string $message
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function parserError($error, $message = '')
    {
        AAM_Core_Console::add(
            sprintf('Error parsing config string: %s', $message),
            $error
        );
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param  array $data
     *
     * @return array
     *
     * @since 6.9.1 https://github.com/aamplugin/advanced-access-manager/issues/226
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.1
     */
    protected function process(array $data)
    {
        $config = array();

        foreach ($data as $section => $block) {
            $config[$section] = array();

            if (is_array($block)) { //this is a INI section, build the nested tree
                $this->buildNestedSection($block, $config[$section]);
            } else { //single property, no need to do anything
                $config[$section] = $this->parseValue($block);
            }
        }

        return $config;
    }

    /**
     * Build the nested config array
     *
     * @param array $data
     * @param array $config
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function buildNestedSection($data, &$config)
    {
        foreach ($data as $key => $value) {
            $root = &$config;

            foreach (explode(self::SEPARATOR, $key) as $level) {
                if (!isset($root[$level])) {
                    $root[$level] = array();
                }
                $root = &$root[$level];
            }
            $root = $this->parseValue($value);
        }
    }

    /**
     * Parse single value
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected function parseValue($value)
    {
        return is_string($value) ? trim($value) : $value;
    }

}