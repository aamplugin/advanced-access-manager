<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Reusable elements for each service
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Service_BaseTrait
{

    /**
     * Single instance of itself
     *
     * @var object
     * @access protected
     *
     * @version 7.0.0
     */
    protected static $instance = null;

    /**
     * Bootstrap the service
     *
     * @return object
     *
     * @param boolean $reload
     * @access public
     *
     * @version 7.0.0
     */
    public static function bootstrap($reload = false)
    {
        if (is_null(self::$instance) || $reload) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get single instance of itself
     *
     * @return object
     *
     * @param boolean $reload
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_instance($reload = false)
    {
        return self::bootstrap($reload);
    }

}