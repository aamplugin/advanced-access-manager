<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login Redirect resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_LoginRedirect
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PreferenceTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::LOGIN_REDIRECT;

}