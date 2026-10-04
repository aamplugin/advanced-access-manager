<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Jwt extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_jwt';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'           => 'jwt',
            'position'      => 65,
            'title'         => __('JWT Tokens', 'advanced-access-manager'),
            'capability'    => self::ACCESS_CAPABILITY,
            'type'          => 'main',
            'view'          => __CLASS__,
            'access_levels' => array(
                AAM_Framework_Type_AccessLevel::USER
            )
        ));
    }
}
