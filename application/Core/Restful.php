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
 * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/390
 * @since 6.9.6  Initial implementation of the class
 *
 * @package AAM
 * @since 6.9.32
 */
class AAM_Core_Restful
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/390
     * @since 6.9.6  Initial implementation of the method
     *
     * @access protected
     * @since 6.9.32
     */
    protected function __construct()
    {
        AAM_Core_Restful_RoleService::bootstrap();
        AAM_Core_Restful_UserService::bootstrap();
    }

}