<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_AccessDeniedRedirect extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_access_denied_redirect';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'redirect',
            'position'   => 30,
            'title'      => __('Access Denied Redirect', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }
}
