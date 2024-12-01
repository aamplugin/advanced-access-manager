<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Framework utilities
 *
 * The collection of methods for various routine tasks
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Utility
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Collection of utilities
     *
     * @var array
     *
     * @access private
     * @static
     * @version 7.0.0
     */
    private $_utility_map = [
        'cache'        => AAM_Framework_Utility_Cache::class,
        'misc'         => AAM_Framework_Utility_Misc::class,
        'config'       => AAM_Framework_Utility_Config::class,
        'redirect'     => AAM_Framework_Utility_Redirect::class,
        'capabilities' => AAM_Framework_Utility_Capabilities::class,
        'caps'         => AAM_Framework_Utility_Capabilities::class,
        'roles'        => AAM_Framework_Utility_Roles::class,
        'users'        => AAM_Framework_Utility_Users::class
    ];

    /**
     * Call any extended methods
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
        try {
            if (is_string($this->_runtime_context)
                && !empty($this->_runtime_context)
            ) {
                $class = $this->_utility_map[$this->_runtime_context];

                if (method_exists($class, $name)) {
                    $result = call_user_func_array("{$class}::{$name}", $args);
                } else {
                    throw new BadMethodCallException(sprintf(
                        'Static method "%s" does not exist in the "%s" utility',
                        $name, $this->_runtime_context
                    ));
                }
            } else {
                throw new RuntimeException(
                    'The utility service invoked incorrectly'
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

}