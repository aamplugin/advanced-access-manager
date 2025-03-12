<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Policy UI manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Policy extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the feature
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_policies';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/policy.php';

    /**
     * Constructor
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct()
    {
        add_filter('aam_iframe_content_filter', function($result, $type, $view) {
            return $this->_render_principal_iframe($result, $type, $view);
        }, 1, 3);
    }

    /**
     * Render access policy principal metabox
     *
     * @param null|string      $result
     * @param string           $type
     * @param AAM_Backend_View $view
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _render_principal_iframe($result, $type, $view)
    {
        if ($type === 'principal') {
            $result = $view->loadTemplate(
                dirname(__DIR__) . '/../tmpl/metabox/principal-iframe.php',
                (object) array(
                    'policyId' => filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
                )
            );
        }

        return $result;
    }

    /**
     * Register Access Policy UI feature
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'policy',
            'position'   => 2,
            'title'      => __('Access Policies', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}