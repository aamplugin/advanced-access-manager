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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_ApiRoute extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_api_routes';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/api-route.php';

    /**
     * Constructor
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct()
    {
        // Customize the user experience
        add_filter('aam_ui_api_route_mode_panel_filter', function() {
            return AAM_Backend_View::get_instance()->loadPartial('api-route-mode');
        });
    }

    /**
     * Register API Routes service
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'route',
            'position'   => 50,
            'title'      => __('API Routes', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}