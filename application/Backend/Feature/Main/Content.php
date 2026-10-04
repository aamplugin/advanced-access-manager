<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Content extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_content';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'post',
            'position'   => 20,
            'title'      => __('Posts & Terms', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }
}
