<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JWT UI manager
 *
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/221
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Backend_Feature_Main_Jwt
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_jwt';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/jwt.php';

    /**
     * Register JWT service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'jwt',
            'position'   => 65,
            'title'      => __('JWT Tokens', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_User::UID
            ),
            'view'       => __CLASS__
        ));
    }

}