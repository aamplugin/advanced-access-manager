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
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/302
 * @since 6.0.0 Initial implementation of the method
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Backend_Feature_Main_Toolbar
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_toolbar';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Toolbar::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/toolbar.php';

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
        add_filter('aam_toolbar_mode_panel_filter', function() {
            return AAM_Backend_View::getInstance()->loadPartial('toolbar-mode');
        });
    }

    /**
     * Get toolbar
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getToolbar()
    {
        return AAM_Service_Toolbar::getInstance()->getToolbarCache();
    }

    /**
     * Register Menu feature
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'toolbar',
            'position'   => 6,
            'title'      => __('Admin Toolbar', AAM_KEY),
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