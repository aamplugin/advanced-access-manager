<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Backend 404 redirect manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_404Redirect
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

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
    const TEMPLATE = 'service/404redirect.php';

    /**
     * Save 404 redirect options
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $param  = AAM_Core_Request::post('param');
        $value  = $this->getFromPost('value');

        $result = AAM_Core_Config::set($param, $value);

        return wp_json_encode(
            array('status' => $result ? 'success' : 'failure')
        );
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