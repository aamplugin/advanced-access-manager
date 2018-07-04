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
 * @package ConfigPress
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @copyright Copyright Vasyl Martyniuk
 */
class AAM_Core_ConfigPress_Reader {

    /**
     * 
     */
    const SEPARATOR = '.';

    /**
     * 
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
     */
    public function parseString($string) {
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
     * 
     * @param type $error
     * @param type $message
     * @throws Exception
     */
    public function parserError($error, $message = '') {
        AAM_Core_Console::add(
            sprintf('Error parsing config string: %s', $message), $error
        );
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param  array $data
     * @return array
     */
    protected function process(array $data) {
        $config = array();
        
        foreach ($data as $section => $data) {
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

            if (is_array($data)) { //this is a INI section, build the nested tree
                $this->buildNestedSection($data, $config[$section]);
            } else { //single property, no need to do anything
                $config[$section] = $this->parseValue($data);
            }
        }

        return $config;
    }

    /**
     * 
     * @param type $section
     * @param type $config
     * @return type
     */
    protected function inherit($section, &$config) {
        $sections = explode(self::INHERIT_KEY, $section);
        $target = trim($sections[0]);
        $parent = trim($sections[1]);

        if (isset($config[$parent])) {
            $config[$target] = $config[$parent];
        }

        return $target;
    }

    /**
     * 
     * @param type $data
     * @param type $config
     */
    protected function buildNestedSection($data, &$config) {
        foreach ($data as $key => $value) {
            $root = &$config;
            // TODO - Remove July 2019
            foreach (explode(self::SEPARATOR, apply_filters('aam-configpress-compatibility-filter', $key)) as $level) {
                if (!isset($root[$level])) {
                    $root[$level] = array();
                }
                $root = &$root[$level];
            }
            $root = $this->parseValue($value);
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function parseValue($value) {
        return is_string($value) ? trim($value) : $value;
    }

}