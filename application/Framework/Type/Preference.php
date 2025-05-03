<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Preference types
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Type_Preference
{

    /**
     * Preference type that represents access denied redirect preferences
     *
     * @version 7.0.0
     */
    const ACCESS_DENIED_REDIRECT = 'access_denied_redirect';

    /**
     * Preference type that represents logout redirect preferences
     *
     * @version 7.0.0
     */
    const LOGOUT_REDIRECT = 'logout_redirect';

    /**
     * Preference type that represents login redirect preferences
     *
     * @version 7.0.0
     */
    const LOGIN_REDIRECT = 'login_redirect';

    /**
     * Preference type that represents 404 (Not Found) redirect preferences
     *
     * @version 7.0.0
     */
    const NOT_FOUND_REDIRECT = 'not_found_redirect';

}