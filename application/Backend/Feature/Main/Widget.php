<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend widgets manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Widget extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the service
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_widgets';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/widget.php';

    /**
     * Get the complete list of admin screens AAM uses to index metaboxes
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    protected function get_screen_urls()
    {
        return [
            esc_url(add_query_arg('init', 'widget', admin_url('index.php')))
        ];
    }

    /**
     * Register metabox service UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'widget',
            'position'   => 10,
            'title'      => __('Widgets', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'        => __CLASS__
        ));
    }

}