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
 * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.4.0 Improved UI functionality with better rules handling
 * @since 6.3.0 Fixed bug with incorrectly handled record editing
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Backend_Feature_Main_Url extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the feature
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = [
        'aam_manage_uri',
        'aam_manage_url_access'
    ];

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/url.php';

    /**
     * Register service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'        => 'url',
            'position'   => 55,
            'title'      => __('URL Access', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ]);
    }

}