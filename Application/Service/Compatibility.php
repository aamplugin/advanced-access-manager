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
 * AAM compatibility service
 *
 * Making sure that nothing gets blown away with major upgrades
 *
 * @package AAM
 * @version 6.0.0
 * @todo Remove Feb 2021
 */
class AAM_Service_Compatibility
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Constructor
     *
     * @access protected
     *
     * @return void
     * @version 6.0.0
     */
    protected function __construct()
    {
        $message  = '[%s] plugin is outdated and was not loaded. Please update it to ';
        $message .= 'the latest available version to make it compatible with AAM';

        // Halt outdated premium plugins
        $addons = array(
            'AAM_PLUS_PACKAGE'   => array(
                'class'   => 'AAM_PlusPackage',
                'name'    => 'Plus Package',
                'version' => '5.0.0'
            ),
            'AAM_ROLE_HIERARCHY' => array(
                'class'   => 'AAM_RoleHierarchy',
                'name'    => 'Role Hierarchy',
                'version' => '3.0.0'
            ),
            'AAM_IP_CHECK'       => array(
                'class'   => 'AAM_IPCheck',
                'name'    => 'IP Check',
                'version' => '4.0.0'
            ),
            'AAM_ECOMMERCE'       => array(
                'class'   => 'AAM_Ecommerce',
                'name'    => 'E-Commerce',
                'version' => '4.0.0'
            )
        );

        foreach($addons as $slug => $addon) {
            if (defined($slug) && version_compare(constant($slug), $addon['version']) === -1) {
                class_alias('AAM', $addon['class']);
                if ($slug !== 'AAM_ECOMMERCE') {
                    AAM_Core_Console::add(sprintf($message, $addon['name']), 'b');
                }
            }
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Compatibility::bootstrap();
}