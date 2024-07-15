<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend 404 redirect manager
 *
 * @since 6.4.0 Changed the way 404 settings are stored
 *              https://github.com/aamplugin/advanced-access-manager/issues/64
 * @since 6.0.0 Initial implementation of the method
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_NotFoundRedirect extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_404_redirect';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/not-found-redirect.php';

    /**
     * Register 404 redirect feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) [
            'uid'        => '404redirect',
            'position'   => 50,
            'title'      => __('404 Redirect', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ]);
    }

}