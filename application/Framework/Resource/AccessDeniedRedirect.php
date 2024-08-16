<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect Redirect resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_AccessDeniedRedirect
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PreferenceInterface
{

    use AAM_Framework_Resource_PreferenceTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::ACCESS_DENIED_REDIRECT;

}