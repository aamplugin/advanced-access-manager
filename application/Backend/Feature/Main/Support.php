<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Support backend service
 *
 * @package AAM
 * @version 6.9.15
 */
class AAM_Backend_Feature_Main_Support extends AAM_Backend_Feature_Abstract
{

    /**
     * HTML template to render
     *
     * @version 6.9.15
     */
    const TEMPLATE = 'service/support.php';

    /**
     * Register support service
     *
     * @return void
     *
     * @access public
     * @version 6.9.15
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'support',
            'position'   => 2,
            'title'      => __('Support', AAM_KEY),
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