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
class AAM_Backend_Feature_Main_Uri
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the feature
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_uri';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Uri::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/uri.php';

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
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'uri',
            'position'   => 55,
            'title'      => __('URL Access', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}