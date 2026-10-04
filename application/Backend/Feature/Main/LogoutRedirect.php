<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_LogoutRedirect extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_logout_redirect';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'           => 'logout_redirect',
            'position'      => 41,
            'title'         => __('Logout Redirect', 'advanced-access-manager'),
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
