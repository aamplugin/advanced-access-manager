<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_ApiRoute extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_api_routes';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'route',
            'position'   => 50,
            'title'      => __('API Routes', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}