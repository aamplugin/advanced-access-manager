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
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/387
 * @since 6.9.10 Initial implementation of the class
 *
 * @version 6.9.31
 */
trait AAM_Framework_Service_BaseTrait
{

    /**
     * Single instance of itself
     *
     * @var static::class
     *
     * @access private
     * @static
     * @version 6.9.10
     */
    private static $_instance = null;

    /**
     * Collection of extended methods
     *
     * @var array
     *
     * @access private
     * @version 6.9.31
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
     * @version 6.9.10
     */
    private $_runtime_context = null;

    /**
     * Context subject
     *
     * @var AAM_Core_Subject
     *
     * @access private
     * @version 6.9.33
     */
    private $_access_level = null;

    /**
     * Instantiate the service
     *
     * @return void
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/387
     * @since 6.9.10 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.31
     */
    protected function __construct()
    {
        // Extend the service instance with additional methods
        $closures = apply_filters(
            'aam_framework_service_closures_filter', [], $this
        );

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
     *
     * @access public
     * @version 6.9.31
     */
    public function __call($name, $args)
    {
        try {
            if ($this->_extended_method_exists($name)) {
                    $result = call_user_func_array(
                        $this->_extended_methods[$name], $args
                    );
            } else {
                throw new BadMethodCallException("Method {$name} does not exist");
            }
        } catch (Exception $e) {
            $result = $this->_handle_error(
                $e,
                // The last argument in any framework method call is context, always!
                is_array($args) ? array_pop($args) : null
            );
        }

        return $result;
    }

    /**
     * Get property
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.33
     */
    public function __get($name)
    {
        try {
            if ($name === 'access_level') {
                $result = $this->_access_level;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get current subject
     *
     * @param mixed $inline_context Runtime context
     *
     * @return AAM_Core_Subject
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_subject($inline_context = null)
    {
        $result = null;

        // Determine if the access level and subject ID are either part of the
        // inline arguments or runtime context when service is requested through the
        // framework service manager
        if (is_array($inline_context)) {
            $context = $inline_context;
        } elseif (is_array($this->_runtime_context)) {
            $context = $this->_runtime_context;
        } else {
            throw new BadMethodCallException('No context provided');
        }

        if (isset($context['subject'])
            && is_a($context['subject'], AAM_Core_Subject::class)) {
            $result = $context['subject'];
        } elseif (!empty($context['access_level'])) {
            $result = AAM_Framework_Manager::subject()->get(
                $context['access_level'],
                isset($context['subject_id']) ? $context['subject_id'] : null
            );
        }

        // Persist the current access level so it can be assessed as property
        $this->_access_level = $result;

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
     * @version 6.9.31
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
     * @param array     $runtime_context
     *
     * @return mixed
     *
     * @access private
     * @version 6.9.33
     */
    private function _handle_error($exception, $runtime_context = null)
    {
        $response = null;

        if (empty($runtime_context)) {
            $context = static::$_instance->_runtime_context;
        } else {
            $context = $runtime_context;
        }

        // Determine what is the proper error handling strategy to pick
        if (!empty($context['error_handling'])) {
            $strategy = $context['error_handling'];
        } else {
            // Do not rely on WP_DEBUG as many website owners forget to turn off
            // debug mode in production
            $strategy = 'wp_trigger_error';
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
     * @version 6.9.10
     */
    public static function get_instance($runtime_context = [])
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        self::$_instance->_runtime_context = $runtime_context;

        return self::$_instance;
    }

}