<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Hooks manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Hooks implements AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Repository of hooks
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_listeners = [];

    /**
     * Get all defined hooks
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_hooks()
    {
        try {
            $result = $this->_get_hooks();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Deny specific hook
     *
     * @param string         $hook
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($hook, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'deny');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow specific hook
     *
     * @param string         $hook
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($hook, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'allow');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alter specific hook's return value
     *
     * @param string         $hook
     * @param mixed          $return
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function alter($hook, $return, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'alter', $return);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alter specific hook's return value by merging it with additional array
     *
     * @param string         $hook
     * @param array          $data
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function merge($hook, array $data, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'merge', $data);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Remove all registered callback function for given hook and return given value
     * instead
     *
     * @param string         $hook
     * @param mixed          $value
     * @param integer|string $priority [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function replace($hook, $value, $priority = 10)
    {
        try {
            $result = $this->_update_permissions($hook, $priority, 'replace', $value);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        try {
            $result = $this->_get_resource()->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Listen to any given hook or all hooks that have permissions defined
     *
     * If $hook argument is not empty, AAM will register listener, however, the
     * permissions will not be persisted in DB.
     *
     * @param array $hook [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function listen($hook = null)
    {
        $result = true;

        try {
            $acl = $this->_get_access_level();

            if (in_array($acl->type, [
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::VISITOR
            ], true)) {
                if (empty($hook)) {
                    foreach($this->_get_hooks() as $hook) {
                        $this->_register_listener($hook);
                    }
                } elseif (is_array($hook) && isset($hook['name'])) {
                    $this->_register_listener(apply_filters(
                        'aam_normalize_hook_filter', $hook
                    ));
                }
            } else {
                throw new LogicException(
                    'Only user and visitor access level can listen to a hook'
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get array of pre-processed hooks
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_hooks()
    {
        $result = [];

        foreach($this->_get_resource()->get_permissions() as $id => $data) {
            list($name, $priority) = explode('|', $id);

            array_push(
                $result,
                apply_filters(
                    'aam_normalize_hook_filter', array_replace($data['access'], [
                    'name'     => $name,
                    'priority' => is_numeric($priority) ? intval($priority) : $priority
                ]))
            );
        }

        return $result;
    }

    /**
     * Update hook permission
     *
     * @param string     $hook
     * @param string|int $priority
     * @param string     $effect
     * @param mixed      $return
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_permissions($hook, $priority, $effect, $return = null)
    {
        $resource_identifier = $this->_normalize_resource_identifier(
            $hook, $priority
        );

        $permission = [
            'effect' => strtolower($effect)
        ];

        if (!is_null($return)) {
            $permission['return'] = $return;
        }

        return $this->_get_resource()->set_permission(
            $resource_identifier, 'access', $permission
        );
    }

    /**
     * Normalize resource identifier
     *
     * @param string     $hook
     * @param int|string $priority
     *
     * @return object
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_resource_identifier($hook, $priority)
    {
        return (object) [
            'name'     => $hook,
            'priority' => $priority
        ];
    }

    /**
     * Control execution of a hook
     *
     * @param array $hook
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _register_listener($hook)
    {
        $this->_listeners[$hook['name']] = $hook;

        if ($hook['effect'] === 'deny') {
            $this->_deny($hook);
        } elseif (in_array($hook['effect'], [ 'merge', 'alter' ], true)) {
            $this->_modify($hook);
        } elseif ($hook['effect'] === 'replace') {
            // Replace the entire chain and return defined value
            $this->_replace($hook);
        }
    }

    /**
     * Deny hook execution
     *
     * @param array $hook
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _deny($hook)
    {
        remove_all_filters($hook['name'], $hook['priority']);
    }

    /**
     * Register hook that modifies filter chain result
     *
     * @param array $hook
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _modify($hook)
    {
        $name = $hook['name'];

        // Register filter modification function only if it was not yet registered
        if (!isset($this->_listeners[$name]['cb'])) {
            $this->_listeners[$name]['cb'] = function($value) use ($name) {
                $effect = $this->_listeners[$name]['effect'];
                $return = $this->_listeners[$name]['return'];

                if ($effect === 'alter') {
                    $value = $this->_override_return_value($value, $return);
                } elseif ($effect === 'merge') {
                    $value = $this->_merge_return_value($value, $return);
                }

                return $value;
            };

            add_filter($name, $this->_listeners[$name]['cb'], $hook['priority']);
        }
    }

    /**
     * Register replace callback
     *
     * @param array $hook
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _replace($hook)
    {
        $name     = $hook['name'];
        $priority = $this->_listeners[$name]['priority'];

        // Register filter replacement function only if it was not yet registered
        if (!isset($this->_listeners[$name]['cb'])) {
            remove_all_filters($name, $priority);

            $this->_listeners[$name]['cb'] = function() use ($name) {
                return $this->_listeners[$name]['return'];
            };

            add_filter($name, $this->_listeners[$name]['cb'], intval($priority));
        }
    }

    /**
     * Override the filter's return value
     *
     * @param mixed $value
     * @param mixed $override
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _override_return_value($value, $override)
    {
        if (is_string($override)) {
            $result = $this->_evaluate_string_expression($override, $value);
        } else if (is_array($override)) {
            $result = $this->_evaluate_possible_array_of_filters($override, $value);
        } else {
            $result = $override;
        }

        return $result;
    }

    /**
     * Merge return value
     *
     * @param array $value
     * @param array $merge_with
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _merge_return_value($value, $merge_with)
    {
        return array_merge(is_array($value) ? $value : [], $merge_with);
    }

    /**
     * Evaluate hook return string expression
     *
     * @param string $expression
     * @param mixed  $input
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _evaluate_string_expression($expression, $input)
    {
        $response = $expression;
        $filter   = $this->_is_filter($expression);

        if (is_string($filter)) {
            $response = $this->_process_filter_modifier($filter, $input);
        }

        return $response;
    }

    /**
     * Evaluate if response is an array of filters
     *
     * @param array $values
     * @param mixed $input
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _evaluate_possible_array_of_filters($values, $input)
    {
        $filters = [];

        foreach($values as $expression) {
            $filter = $this->_is_filter($expression);

            if (is_string($filter)) {
                array_push($filters, $filter);
            }
        }

        // If all the values in the array are filters, then run the chain of filters
        if (count($values) === count($filters)) {
            $response = $input;

            foreach($filters as $filter) {
                $response = $this->_process_filter_modifier($filter, $response);
            }
        } else {
            $response = $values;
        }

        return $response;
    }

    /**
     * Determine if return value is filter
     *
     * @param string $expression
     *
     * @return string|boolean
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_filter($expression)
    {
        $matches = array();

        // Evaluating if a string is &:filter(...)
        $match = preg_match('/^\&:filter\(([^)]+)\)$/i', $expression, $matches);

        return $match === 1 ? $matches[1] : false;
    }

    /**
     * Process the "filter" modifier
     *
     * @param string $modifier
     * @param mixed  $input
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _process_filter_modifier($modifier, $input)
    {
        $response = [];

        if (is_iterable($input)) {
            $ops = implode('|', array_map('preg_quote', [
                '==', '*=', '^=', '$=', '!=', '!in', 'in', '∉', '∈', '>', '<', '>=',
                '<='
            ]));

            if (preg_match("/^(.*)\s+({$ops})\s+(.*)\$/i", $modifier, $matches)) {
                foreach($input as $key => $value) {
                    $source = trim($matches[1]) === '$key' ? $key : $value;
                    $a = $this->misc->get(
                        $source,
                        str_replace(array('$key', '$value'), '', $matches[1])
                    );
                    $b = trim($matches[3]," \t\"'[]");

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
                    } elseif ($matches[2] === '>') { // Greater Than?
                        $include = $a > $b;
                    } elseif ($matches[2] === '<') { // Less Than?
                        $include = $a < $b;
                    } elseif ($matches[2] === '>=') { // Greater or Equals to?
                        $include = $a >= $b;
                    } elseif ($matches[2] === '<=') { // Less of Equals to?
                        $include = $a <= $b;
                    } elseif (in_array($matches[2], array('!in', '∉'), true)) {
                        $include = !in_array($a, explode(',', $b), true);
                    } elseif (in_array($matches[2], array('in', '∈'), true)) {
                        $include = in_array($a, explode(',', $b), true);
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
     * Get hooks resource
     *
     * @return AAM_Framework_Resource_Hook
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );
    }

}