<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Get Started manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_GetStarted  extends AAM_Backend_Feature_Abstract {
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/get-started.phtml';
    }
    
    /**
     * Register 404 redirect feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'get-started',
            'position'   => 1,
            'title'      => __('Get Started', AAM_KEY),
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Default::UID,
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID
            ),
            'option'     => 'core.settings.getStarted',
            'view'       => __CLASS__
        ));
    }

}