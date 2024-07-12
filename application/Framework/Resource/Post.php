<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     *
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * Check if particular access property is enabled
     *
     * Examples of such a access property is "restricted", "hidden", etc.
     *
     * @param string $property
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     * @todo - Should this be moved to Interface for all resources?
     */
    public function is($action)
    {
        $result = null;

        if (array_key_exists($action, $this->_settings)) {
            if (is_bool($this->_settings[$action])) {
                $result = $this->_settings[$action];
            } else {
                $result = !empty($this->_settings[$action]['enabled']);
            }
        }

        return apply_filters(
            'aam_post_action_denied_filter', $result, $action, $this
        );
    }

    /**
     * Alias for the `is` method
     *
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     * @todo - Should this be moved to Interface for all resources?
     */
    public function has($action)
    {
        return $this->is($action);
    }

    /**
     * Whether certain action is allowed or not
     *
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     * @todo - Should this be moved to Interface for all resources?
     */
    public function is_allowed_to($action)
    {
        // Normalize some names to improve code verbosity
        $lower_case = strtolower($action);

        if (in_array($lower_case, array('read', 'access'))) {
            $action = 'restricted';
        } elseif ($lower_case === 'list') {
            $action = 'hidden';
        }

        $result = $this->is($action);

        return $result !== null ? !$result : null;
    }

    /**
     * Get core instance property
     *
     * @param  $name
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function __get($name)
    {
        if (property_exists($this->_core_instance, $name)) {
            $result = $this->_core_instance->{$name};
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Invoke core instance method
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        if (method_exists($this->_core_instance, $name)) {
            $result = call_user_func_array([ $this->_core_instance, $name ], $args);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Initialize additional properties
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $post = get_post($this->_internal_id);

        if (is_a($post, 'WP_Post')) {
            $this->_core_instance = $post;
        } else {
            throw new OutOfRangeException(
                "Post with ID {$this->_internal_id} does not exist"
            );
        }
    }

}