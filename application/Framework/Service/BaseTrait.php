<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract base for all services
 *
 * @package AAM
 *
 * @version 7.0.0
 */
trait AAM_Framework_Service_BaseTrait
{

    /**
     * Collection of extended methods
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_extended_methods = [];

    /**
     * The runtime context
     *
     * This context typically contains information about current subject
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_runtime_context = null;

    /**
     * Instantiate the service
     *
     * @param array $runtime_context
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct($runtime_context)
    {
        $this->_runtime_context = $runtime_context;

        // Extend the service instance with additional methods
        $closures = apply_filters('aam_framework_service_methods_filter', [], $this);

        if (is_array($closures)) {
            foreach($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }

        if (method_exists($this, 'initialize_hooks')) {
            $this->initialize_hooks();
        };
    }

    /**
     * Call any extended methods
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        try {
            if ($this->_extended_method_exists($name)) {
                $result = call_user_func_array(
                    $this->_extended_methods[$name], $args
                );
            } elseif (AAM_Framework_Manager::_()->has_service($name)) {
                $result = AAM_Framework_Manager::_()->{$name}(...$args);
            } else {
                throw new BadMethodCallException("Method {$name} does not exist");
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get property
     *
     * @param string $name
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        $result = null;

        try {
            if ($name === 'access_level') {
                $result = $this->_get_access_level();
            } elseif (AAM_Framework_Manager::_()->has_utility($name)) {
                $result = AAM_Framework_Manager::_()->{$name};
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Are permissions customized for current access level
     *
     * Determine if permissions for the resource are customized for the current
     * access level. Permissions are considered customized if there is at least one
     * permission explicitly allowed or denied.
     *
     * @return boolean
     * @version 7.0.0
     */
    public function is_customized()
    {
        try {
            $result = $this->_get_resource()->is_customized();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get current subject
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_access_level()
    {
        $result = null;

        if (is_array($this->_runtime_context)) {
            $context = $this->_runtime_context;
        } else {
            throw new BadMethodCallException('No context provided');
        }

        if (isset($context['access_level'])) {
            $result = $context['access_level'];
        } elseif (!empty($context['access_level_type'])) {
            $result = $this->access_levels->get(
                $context['access_level_type'],
                isset($context['access_level_id']) ? $context['access_level_id'] : null
            );
        }

        return $result;
    }

    /**
     * Check if extended method exists
     *
     * @param string $name
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _extended_method_exists($name)
    {
        return isset($this->_extended_methods[$name])
            && is_callable($this->_extended_methods[$name]);
    }

    /**
     * Handle error
     *
     * @param Exception $exception
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    private function _handle_error($exception)
    {
        $response = null;

        // Determine what is the proper error handling strategy to pick
        if (!empty($this->_runtime_context['error_handling'])) {
            $strategy = $this->_runtime_context['error_handling'];
        } else {
            // Do not rely on WP_DEBUG as many website owners forget to turn off
            // debug mode in production
            $strategy = 'wp_error';
        }

        if ($strategy === 'exception') {
            throw $exception;
        } elseif ($strategy === 'wp_error') {
            $response = new WP_Error('error', $exception->getMessage());
        } else {
            wp_trigger_error(static::class, $exception->getMessage());
        }

        return $response;
    }

    /**
     * Bootstrap and return an instance of the service
     *
     * @param array $runtime_context
     *
     * @return static::class
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function get_instance($runtime_context = [])
    {
        $result = new self($runtime_context);

        return $result;
    }

}