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
 * AAM Network backend service
 *
 * @package AAM
 * @version 6.7.0
 */
class AAM_Backend_Feature_Main_Network  extends AAM_Backend_Feature_Abstract
{

    /**
     * HTML template to render
     *
     * @version 6.7.0
     */
    const TEMPLATE = 'service/network.php';

    /**
     * Register network service
     *
     * @return void
     *
     * @access public
     * @version 6.7.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'network',
            'position'   => 99,
            'title'      => __('Network', AAM_KEY),
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