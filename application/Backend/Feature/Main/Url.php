<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URI service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Url extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the feature
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = [
        'aam_manage_uri',
        'aam_manage_url_access'
    ];

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/url.php';

    /**
     * Register service UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
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