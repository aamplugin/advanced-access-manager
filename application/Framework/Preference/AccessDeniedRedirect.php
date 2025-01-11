<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect preferences
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_AccessDeniedRedirect
implements AAM_Framework_Preference_Interface
{

    use AAM_Framework_Preference_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Preference::ACCESS_DENIED_REDIRECT;

}