<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Widget extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_widgets';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'widget',
            'position'   => 10,
            'title'      => __('Widgets', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'        => __CLASS__
        ));
    }
}
