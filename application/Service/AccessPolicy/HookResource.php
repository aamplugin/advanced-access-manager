<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Policy Hook resource handler
 *
 * @package AAM
 * @version 6.9.17
 */
class AAM_Service_AccessPolicy_HookResource
{

    /**
     * Supported comparison operators in &:filter(...) expression
     *
     * @version 6.9.17
     */
    const SUPPORTED_COMP_OPS = array('==', '*=', '^=', '$=', '!=');

    /**
     * Parse a statement for give resource
     *
     * @param string $resource
     * @param array  $statement
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public static function parse($resource, $statement)
    {
        $parts    = explode(':', $resource);
        $hook     = trim($parts[0]);
        $priority = empty($parts[1]) ? 10 : $parts[1];

        $effect = isset($statement['Effect']) ? strtolower($statement['Effect']) : null;

        if ($effect === 'deny') {
            self::_remove_hook($hook, $priority);
        } elseif (in_array($effect, array('apply', 'override'))) {
            if (isset($statement['Response'])) {
                self::_override_return_value($hook, $priority, $statement['Response']);
            }
        } elseif ($effect === 'merge' && is_array($statement['Response'])) {
            self::_merge_return_value($hook, $priority, $statement['Response']);
        }
    }

    /**
     * Remove hook
     *
     * @param string $hook
     * @param int    $priority
     *
     * @return void
     *
     * @access private
     * @version 6.9.17
     */
    private static function _remove_hook($hook, $priority)
    {
        $priority = apply_filters('aam_hook_resource_priority', $priority);

        if (is_bool($priority) || is_numeric($priority)) {
            remove_all_filters($hook, $priority);
        }
    }

    /**
     * Override the filter's return value
     *
     * @param string $hook
     * @param int    $priority
     * @param mixed  $override
     *
     * @return void
     *
     * @access private
     * @version 6.9.17
     */
    private static function _override_return_value($hook, $priority, $override)
    {
        add_filter($hook, function($response) use ($override) {
            if (is_string($override)) {
                $result = self::_evaluate_string_expression($override, $response);
            } else {
                $result = $override;
            }

            return $result;
        }, $priority);
    }

    /**
     * Evaluate hook return string expression
     *
     * @param string $expression
     * @param mixed  $input
     *
     * @return mixed
     *
     * @access private
     * @version 6.9.17
     */
    private static function _evaluate_string_expression($expression, $input)
    {
        $response = $input;

        // Evaluating if a string is &:filter(...)
        if (preg_match('/^\&:filter\(([^)]+)\)$/i', $expression, $matches)) {
            $response = self::_process_filter_modifier($matches[1], $input);
        }

        return $response;
    }

    /**
     * Process the "filter" modifier
     *
     * @param string $modifier
     * @param mixed  $input
     *
     * @return array
     *
     * @access private
     * @version 6.9.17
     */
    private static function _process_filter_modifier($modifier, $input)
    {
        $response = array();

        if (is_iterable($input)) {
            $ops = implode('|', array_map('preg_quote', self::SUPPORTED_COMP_OPS));

            if (preg_match("/^(.*)({$ops})(.*)\$/i", $modifier, $matches)) {
                foreach($input as $key => $value) {
                    $source = trim($matches[1]) === '$key' ? $key : $value;
                    $a = AAM_Core_Policy_Xpath::get_value_by_xpath(
                        $source, str_replace(array('$key', '$value'), '', $matches[1])
                    );
                    $b = trim($matches[3]," \t\"'");

                    $include = false;

                    if ($matches[2] === '==') { // Equals?
                        $include = $a == $b;
                    } elseif ($matches[2] === '*=') { // Contains?
                        $include = strpos($a, $b) !== false;
                    } elseif ($matches[2] === '^=') { // Starts with?
                        $include = strpos($a, $b) === 0;
                    } elseif ($matches[2] === '$=') { // Ends with?
                        $include = preg_match('/' . preg_quote($b) . '$/', $a) === 1;
                    } elseif ($matches[2] === '!=') { // Not Equals?
                        $include = $a != $b;
                    }

                    if ($include) {
                        $response[$key] = $value;
                    }
                }

                $response = is_object($input) ? (object) $response : $response;
            }
        }

        return $response;
    }

    /**
     * Merge return value
     *
     * @param string $hook
     * @param int    $priority
     * @param array  $to_merge
     *
     * @return void
     *
     * @access private
     * @version 6.9.17
     */
    private static function _merge_return_value($hook, $priority, $to_merge)
    {
        add_filter($hook, function($response) use ($to_merge) {
            return array_merge(is_array($response) ? $response : array(), $to_merge);
        }, $priority);
    }

}