<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Welcome backend service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Welcome  extends AAM_Backend_Feature_Abstract
{

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/welcome.php';

    /**
     * Register welcome service
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'welcome',
            'position'   => 1,
            'title'      => __('Welcome', 'advanced-access-manager'),
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}