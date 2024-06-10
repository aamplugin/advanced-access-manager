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
    protected function __construct() {
        // Extend the service instance with additional methods
        $closures = apply_filters('aam_framework_service_closures_filter', [], $this);

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
        if ($this->_extended_method_exists($name)) {
                $response = call_user_func_array(
                    $this->_extended_methods[$name], $args
                );
        } else {
            throw new Exception("Method {$name} does not exist");
        }

        return $response;
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
     * Get current subject
     *
     * @param mixed $inline_context Runtime context
     *
     * @return AAM_Core_Subject
     *
     * @access private
     * @version 6.9.10
     */
    private function _get_subject($inline_context)
    {
        // Determine if the access level and subject ID are either part of the
        // inline arguments or runtime context when service is requested through the
        // framework service manager
        if ($inline_context) {
            $context = $inline_context;
        } elseif ($this->_runtime_context) {
            $context = $this->_runtime_context;
        } else {
            throw new InvalidArgumentException('No context provided');
        }

        if (isset($context['subject'])
            && is_a($context['subject'], AAM_Core_Subject::class)) {
            $subject = $context['subject'];
        } elseif (empty($context['access_level'])) {
            throw new InvalidArgumentException('The access_level is required');
        } else {
            $subject  = AAM_Framework_Manager::subject()->get(
                $context['access_level'],
                isset($context['subject_id']) ? $context['subject_id'] : null
            );
        }

        return $subject;
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
    public static function get_instance($runtime_context = null)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        self::$_instance->_runtime_context = $runtime_context;

        return self::$_instance;
    }

}