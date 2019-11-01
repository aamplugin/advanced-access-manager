<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Reusable elements for each service
 *
 * @package AAM
 * @version 6.0.0
 */
trait AAM_Core_Contract_ServiceTrait
{

    /**
     * Single instance of itself
     *
     * @var object
     *
     * @access protected
     * @version 6.0.0
     */
    protected static $instance = null;

    /**
     * Bootstrap the service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
    }

    /**
     * Get single instance of itself
     *
     * @return object
     *
     * @access public
     * @version 6.0.0
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::bootstrap();
        }

        return self::$instance;
    }

}