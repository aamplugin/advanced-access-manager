<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Url extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = [
        'aam_manage_uri',
        'aam_manage_url_access'
    ];

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'        => 'url',
            'position'   => 55,
            'title'      => __('URL Access', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ]);
    }
}
