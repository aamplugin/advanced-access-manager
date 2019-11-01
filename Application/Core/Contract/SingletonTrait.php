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
 * Reusable elements for singletons
 *
 * @package AAM
 * @version 6.0.0
 */
trait AAM_Core_Contract_SingletonTrait
{

    /**
     * Single instance of itself
     *
     * @var object
     *
     * @access private
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    { }

    /**
     * Bootstrap the object
     *
     * @return self
     *
     * @access public
     * @version 6.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return self
     *
     * @access public
     * @version 6.0.0
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = self::bootstrap();
        }

        return self::$_instance;
    }

}