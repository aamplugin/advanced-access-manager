<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Logout Redirect resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_LogoutRedirect
    implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource type
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::LOGOUT_REDIRECT;

    /**
     * @inheritDoc
     */
    public function merge_settings($incoming_settings)
    {
        return array_replace_recursive($incoming_settings, $this->_settings);
    }

}