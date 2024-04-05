<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Policy Hook controller
 *
 * @package AAM
 * @version 6.9.25
 */
class AAM_Service_AccessPolicy_HookController
{

    /**
     * Supported comparison operators in &:filter(...) expression
     *
     * @version 6.9.25
     */
    const SUPPORTED_COMP_OPS = array('==', '*=', '^=', '$=', '!=');

    /**
     * Single instance of itself
     *
     * @var AAM_Service_AccessPolicy_HookController
     *
     * @access private
     * @static
     *
     * @version 6.9.25
     */
    private static $_instance = null;

    /**
     * Collection of targeting hooks
     *
     * @var array
     *
     * @access private
     * @version 6.9.25
     */
    private $_hooks = array();

    /**
     * Constructor
     *
     * @access protected
     * @version 6.9.25
     */
    protected function __construct()
    {
        $manager = AAM::api()->getAccessPolicyManager();
        $found   = $manager->getResources(AAM_Core_Policy_Resource::HOOK);

        foreach($found as $target => $statement) {
            $resource = explode(':', $target);
            $hook     = trim($resource[0]);
            $priority = apply_filters(
                'aam_hook_resource_priority',
                isset($resource[1]) ? $resource[1] : 10
            );

            $this->_hooks[$hook] = array(
                'priority' => is_numeric($priority) ? intval($priority) : $priority,
                'return'   => isset($statement['Response']) ? $statement['Response'] : null,
                'effect'   => strtolower($statement['Effect'])
            );
        }

        if (count($this->_hooks) > 0) { // Only register if there is at least one hook
            add_filter('all', array($this, 'control'));
        }
    }

    /**
     * Control execution of a current hook
     *
     * This method is invoked each time WordPress core functions do_actions or
     * apply_filters triggered
     *
     * @param string $hook_name
     *
     * @return void
     *
     * @access public
     * @version 6.9.25
     */
    public function control($hook_name)
    {
        if (isset($this->_hooks[$hook_name])) {
            // Process the "deny" effect
            $effect = $this->_hooks[$hook_name]['effect'];

            if ($effect === 'deny') {
                $this->deny_hook($hook_name);
            } elseif (in_array($effect, array('apply', 'override', 'merge'), true)) {
                $this->register_modify_callback($hook_name);
            } elseif ($effect === 'replace') {
                // Replace the entire chain and return defined value
                $this->register_replace_callback($hook_name);
            }
        }
    }

    /**
     * Reset all registered hooks
     *
     * @return void
     *
     * @access public
     * @version 6.9.25
     */
    public function reset()
    {
        foreach($this->_hooks as $hook_name => $data) {
            if (isset($data['callback'])) {
                remove_filter(
                    $hook_name,
                    $data['callback'],
                    $data['registered_priority']
                );
            }
        }

        remove_filter('all', array($this, 'control'));

        $this->_hooks = array();
    }

    /**
     * Deny hook execution
     *
     * @param string $hook_name
     *
     * @return void
     *
     * @access protected
     * @version 6.9.25
     */
    protected function deny_hook($hook_name)
    {
        $priority = $this->_hooks[$hook_name]['priority'];

        if (is_bool($priority) || is_numeric($priority)) {
            remove_all_filters($hook_name, $priority);
        }
    }

    /**
     * Register hook that modifies filter chain result
     *
     * @param string $hook_name
     *
     * @return void
     *
     * @access protected
     * @version 6.9.25
     */
    protected function register_modify_callback($hook_name)
    {
        $priority   = $this->_hooks[$hook_name]['priority'];
        $r_priority = $priority === false ? PHP_INT_MAX : intval($priority);

        // Re-register the override callback function to ensure it is always
        // in the end
        if (isset($this->_hooks[$hook_name]['callback'])) {
            remove_filter(
                $hook_name, $this->_hooks[$hook_name]['callback'], $r_priority
            );
        }

        $this->_hooks[$hook_name]['callback'] = function($value) use ($hook_name) {
            $effect = $this->_hooks[$hook_name]['effect'];
            $return = $this->_hooks[$hook_name]['return'];

            if (in_array($effect, array('apply', 'override'), true)) {
                $value = $this->_override_return_value($value, $return);
            } elseif ($effect === 'merge') {
                $value = $this->_merge_return_value($value, $return);
            } else {
                _doing_it_wrong(
                    'AAM Override Hook',
                    "The '{$effect}' is not supported",
                    AAM_VERSION
                );
            }

            return $value;
        };

        add_filter($hook_name, $this->_hooks[$hook_name]['callback'], $r_priority);

        $this->_hooks[$hook_name]['registered_priority'] = $r_priority;
    }

    /**
     * Register replace callback
     *
     * @param string $hook_name
     *
     * @return void
     *
     * @access protected
     * @version 6.9.25
     */
    protected function register_replace_callback($hook_name)
    {
        $priority   = $this->_hooks[$hook_name]['priority'];
        $r_priority = is_bool($priority) ? 10 : $priority;

        remove_all_filters($hook_name, $priority);

        $this->_hooks[$hook_name]['callback'] = function() use ($hook_name) {
            return $this->_hooks[$hook_name]['return'];
        };

        add_filter($hook_name, $this->_hooks[$hook_name]['callback'], $r_priority);

        $this->_hooks[$hook_name]['registered_priority'] = $r_priority;
    }

    /**
     * Override the filter's return value
     *
     * @param mixed $value
     * @param mixed $override
     *
     * @return void
     *
     * @access private
     * @version 6.9.25
     */
    private function _override_return_value($value, $override)
    {
        if (is_string($override)) {
            $result = $this->_evaluate_string_expression($override, $value);
        } else {
            $result = $override;
        }

        return $result;
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
     * @version 6.9.25
     */
    private function _evaluate_string_expression($expression, $input)
    {
        $response = $input;

        // Evaluating if a string is &:filter(...)
        if (preg_match('/^\&:filter\(([^)]+)\)$/i', $expression, $matches)) {
            $response = $this->_process_filter_modifier($matches[1], $input);
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
     * @version 6.9.25
     */
    private function _process_filter_modifier($modifier, $input)
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
     * @param array $value
     * @param array $merge_with
     *
     * @return void
     *
     * @access private
     * @version 6.9.25
     */
    private function _merge_return_value($value, $merge_with)
    {
        return array_merge(is_array($value) ? $value : array(), $merge_with);
    }

    /**
     * Bootstrap the controller
     *
     * @param boolean $reload
     *
     * @return AAM_Service_AccessPolicy_HookController
     *
     * @access public
     * @static
     *
     * @version 6.9.25
     */
    public static function bootstrap($reload = false)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        } elseif ($reload) {
            // Remove all registered hooks
            self::$_instance->reset();

            self::$_instance = new self;
        }

        return self::$_instance;
    }

}