<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Policy extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_policies';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'policy',
            'position'   => 2,
            'title'      => __('Access Policies', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }
}
