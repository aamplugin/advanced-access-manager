<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace Firebase\JWT;

/**
 * Dummy class to avoid fatal errors for any third-party that uses AAM Firebase
 * library
 *
 * @version 6.9.0
 *
 * @todo Remove in 7.0.0
 */
class JWT
{

    /**
     * Dummy property
     *
     * @var null
     * @deprecated version
     *
     * @version 6.9.0
     */
    public static $supported_algs = null;

    /**
     * Dummy method
     *
     * @return void
     * @deprecated version
     *
     * @version 6.9.0
     */
    public static function decode()
    {
        _deprecated_function(
            __CLASS__ . '::' . __METHOD__, '6.8.5', 'AAM_Core_Jwt_Manager::decode'
        );
    }

    /**
     * Dummy method
     *
     * @return void
     * @deprecated version
     *
     * @version 6.9.0
     */
    public static function encode()
    {
        _deprecated_function(
            __CLASS__ . '::' . __METHOD__, '6.8.5', 'AAM_Core_Jwt_Manager::encode'
        );
    }

}