<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_NotFoundRedirect extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_404_redirect';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'        => '404redirect',
            'position'   => 50,
            'title'      => __('404 Redirect', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ]);
    }
}
