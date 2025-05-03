<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Toolbar manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_AdminToolbar extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_admin_toolbar';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/admin-toolbar.php';

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
        add_filter('aam_ui_admin_toolbar_mode_panel_filter', function() {
            return AAM_Backend_View::get_instance()->loadPartial(
                'admin-toolbar-mode'
            );
        });
    }

    /**
     * Register Menu feature
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'           => 'toolbar',
            'position'      => 6,
            'title'         => __('Admin Toolbar', 'advanced-access-manager'),
            'capability'    => self::ACCESS_CAPABILITY,
            'type'          => 'main',
            'view'          => __CLASS__,
            'access_levels' => array(
                AAM_Framework_Type_AccessLevel::ROLE,
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ALL
            )
        ));
    }

}