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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Framework_Service_NotFoundRedirect
{

    use AAM_Framework_Service_BaseTrait,
        AAM_Framework_Service_RedirectTrait;

    /**
     * Redirect type
     *
     * @version 6.9.12
     */
    const REDIRECT_TYPE = '404';

    /**
     * Object type
     *
     * @version 6.9.33
     */
    const OBJECT_TYPE = AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE;

    /**
     * Redirect type aliases
     *
     * To be a bit more verbose, we are renaming the legacy rule types to something
     * that is more intuitive
     *
     * @version 6.9.26
     */
    const REDIRECT_TYPE_ALIAS = array(
        'default'  => 'default',
        'page'     => 'page_redirect',
        'url'      => 'url_redirect',
        'callback' => 'trigger_callback',
        'login'    => 'login_redirect'
    );

    /**
     * Array of allowed HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_STATUS_CODES = array(
        'default'          => null,
        'page_redirect'    => array('3xx'),
        'url_redirect'     => array('3xx'),
        'login_redirect'   => null,
        'trigger_callback' => array('3xx', '4xx', '5xx')
    );

    /**
     * Array of default HTTP status codes
     *
     * @version 6.9.26
     */
    const HTTP_DEFAULT_STATUS_CODES = array(
        'default'          => null,
        'page_redirect'    => 307,
        'url_redirect'     => 307,
        'login_redirect'   => null,
        'trigger_callback' => 404
    );

    /**
     * Get object
     *
     * @param array $inline_context
     *
     * @return AAM_Core_Object
     */
    private function _get_object($inline_context)
    {
        return $this->_get_subject($inline_context)->getObject(
            AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
        );
    }

}