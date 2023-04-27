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
 * @version 6.9.10
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
     * @access protected
     * @version 6.9.10
     */
    protected function __construct() {}

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