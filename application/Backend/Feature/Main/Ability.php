<?php

/** Workspace service registration. @package AAM */
class AAM_Backend_Feature_Main_Ability extends AAM_Backend_Feature_Abstract
{
    const ACCESS_CAPABILITY = 'aam_manage_abilities';

    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'        => 'ability',
            'position'   => 55,
            'title'      => __('Abilities & MCP', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__,
            'access_levels' => array(
                AAM_Framework_Type_AccessLevel::ROLE,
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ALL
            )
        ]);
    }

}