<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress API manager
 *
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/274
 * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/131
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Backend_Feature_Main_ApiRoute extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_api_routes';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/api-route.php';

    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     * @version 6.9.13
     */
    public function __construct()
    {
        // Customize the user experience
        add_filter('aam_ui_api_route_mode_panel_filter', function() {
            return AAM_Backend_View::getInstance()->loadPartial('api-route-mode');
        });
    }

    /**
     * Register API Routes service
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'route',
            'position'   => 50,
            'title'      => __('API Routes', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}