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
 * ConfigPress Reader
 *
 * Parse configuration string
 *
 * @package AAM
 * @version 6.0.0
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
     * Default section inheritance indicator
     *
     * @version 6.0.0
     */
    const INHERIT_KEY = ':';

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
     * @access protected
     * @version 6.0.0
     */
    protected function process(array $data)
    {
        $config = array();

        foreach ($data as $section => $block) {
            //check if section has parent section or property
            if (preg_match('/[\s\w]{1}' . self::INHERIT_KEY . '[\s\w]{1}/', $section)) {
                $section = $this->inherit($section, $config);
            } else {
                //evaluate the section and if not false move forward
                $evaluator = new AAM_Core_ConfigPress_Evaluator($section);
                if ($evaluator->evaluate()) {
                    $section = $evaluator->getAlias();
                    $config[$section] = array();
                } else {
                    continue; //conditional section that did not meet condition
                }
            }

            if (is_array($block)) { //this is a INI section, build the nested tree
                $this->buildNestedSection($block, $config[$section]);
            } else { //single property, no need to do anything
                $config[$section] = $this->parseValue($block);
            }
        }

        return $config;
    }

    /**
     * Inherit settings from different section
     *
     * @param string $section
     * @param array  $config
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function inherit($section, &$config)
    {
        $sections = explode(self::INHERIT_KEY, $section);
        $target = trim($sections[0]);
        $parent = trim($sections[1]);

        if (isset($config[$parent])) {
            $config[$target] = $config[$parent];
        }

        return $target;
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