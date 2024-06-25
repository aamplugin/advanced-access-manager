<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend capability manager
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.8.5  https://github.com/aamplugin/advanced-access-manager/issues/218
 * @since 6.8.0  https://github.com/aamplugin/advanced-access-manager/issues/195
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Backend_Feature_Main_Capability
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_capabilities';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/capability.php';

    /**
     * Register Capability service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'capability',
            'position'   => 15,
            'title'      => __('Capabilities', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID
            ),
            'view'       => __CLASS__
        ));
    }

}