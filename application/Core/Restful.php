<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API facade
 *
 * @package AAM
 * @since 6.9.6
 */
class AAM_Core_Restful
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @since 6.9.6
     */
    protected function __construct()
    {
        AAM_Core_Restful_Role::bootstrap();
    }

}