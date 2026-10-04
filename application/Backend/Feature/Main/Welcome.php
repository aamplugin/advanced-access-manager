<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Welcome extends AAM_Backend_Feature_Abstract
{
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'welcome',
            'position'   => 1,
            'title'      => __('Welcome', 'advanced-access-manager'),
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }
}
