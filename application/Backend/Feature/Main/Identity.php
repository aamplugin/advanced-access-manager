<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Identity extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_identities';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'identity',
            'position'   => 60,
            'title'      => __('Identity Governance', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }
}
