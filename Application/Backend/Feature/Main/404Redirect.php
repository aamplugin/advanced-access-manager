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
class AAM_Backend_Feature_Main_404Redirect
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_404_redirect';

    /**
     * Type of AAM core object
     *
     * @version 6.4.0
     */
    const OBJECT_TYPE = AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/404redirect.php';

    /**
     * Get option value
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.4.0
     */
    public function getOption($name, $default = null)
    {
        $object = $this->getSubject()->getObject(self::OBJECT_TYPE);
        $option = $object->getOption();

        return (!empty($option[$name]) ? $option[$name] : $default);
    }

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
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => '404redirect',
            'position'   => 50,
            'title'      => __('404 Redirect', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Default::UID,
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID
            ),
            'view'       => __CLASS__
        ));
    }

}