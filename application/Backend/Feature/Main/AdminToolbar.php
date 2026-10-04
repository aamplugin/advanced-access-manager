<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_AdminToolbar extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_admin_toolbar';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'           => 'toolbar',
            'position'      => 6,
            'title'         => __('Admin Toolbar', 'advanced-access-manager'),
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
