<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Logout Redirect manager
 *
 * @package AAM
 * @version 6.9.12
 */
class AAM_Framework_Service_LogoutRedirect
    extends AAM_Framework_Service_RedirectAbstract
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Redirect type
     *
     * @version 6.9.12
     */
    const REDIRECT_TYPE = 'logout';

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Core_Object
     */
    protected function get_object($inline_context)
    {
        return $this->_get_subject($inline_context)->getObject(
            AAM_Core_Object_LogoutRedirect::OBJECT_TYPE
        );
    }

}