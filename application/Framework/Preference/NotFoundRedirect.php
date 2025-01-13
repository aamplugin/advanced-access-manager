<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Not Found Redirect preferences
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_NotFoundRedirect
implements AAM_Framework_Preference_Interface
{

    use AAM_Framework_Preference_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Preference::NOT_FOUND_REDIRECT;

}