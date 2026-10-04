<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_LoginRedirect extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_login_redirect';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'           => 'login_redirect',
            'position'      => 40,
            'title'         => __('Login Redirect', 'advanced-access-manager'),
            'capability'    => self::ACCESS_CAPABILITY,
            'type'          => 'main',
            'view'          => __CLASS__,
            'access_levels' => [
                AAM_Framework_Type_AccessLevel::ROLE,
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ALL
            ]
        ]);
    }
}
