<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service 404 Redirect manager
 *
 * @package AAM
 * @version 6.9.12
 */
class AAM_Framework_Service_NotFoundRedirect
    extends AAM_Framework_Service_RedirectAbstract
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Redirect type
     *
     * @version 6.9.12
     */
    const REDIRECT_TYPE = '404';

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
            AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
        );
    }

}