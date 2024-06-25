<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend menu manager
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/293
 * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/240
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Backend_Feature_Main_Menu
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_admin_menu';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Menu::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/menu.php';

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
        add_filter('aam_backend_menu_mode_panel_filter', function() {
            return AAM_Backend_View::getInstance()->loadPartial('backend-menu-mode');
        });
    }

    /**
     * Register Admin Menu feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'admin_menu',
            'position'   => 5,
            'title'      => __('Backend Menu', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}