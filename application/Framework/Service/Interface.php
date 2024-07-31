<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Framework service interface
 *
 * @package AAM
 *
 * @version 7.0.0
 */
interface AAM_Framework_Service_Interface
{

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
    public static function get_instance($runtime_context = []);

}