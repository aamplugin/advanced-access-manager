<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login redirect
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_LoginRedirect extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_login_redirect';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/login-redirect.php';

    /**
     * Register login redirect feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'           => 'login_redirect',
            'position'      => 40,
            'title'         => __('Login Redirect', AAM_KEY),
            'capability'    => self::ACCESS_CAPABILITY,
            'type'          => 'main',
            'view'          => __CLASS__,
            'access_levels' => array(
                AAM_Framework_Type_AccessLevel::ROLE,
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ALL
            )
        ));
    }

}