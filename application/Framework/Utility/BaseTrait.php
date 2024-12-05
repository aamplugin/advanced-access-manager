<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait for all utility classes
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Utility_BaseTrait
{

    /**
     * Single instance of a class
     *
     * @var AAM_Framework_Utility_Interface
     */
    private static $_instance = null;

    /**
     * Construct
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct() {}

    /**
     * Bootstrap the utility class
     *
     * @return AAM_Framework_Utility_Interface
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}