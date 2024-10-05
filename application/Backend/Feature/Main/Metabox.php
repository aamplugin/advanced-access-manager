<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend metaboxes & widgets manager
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/358
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/301
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.7.4  https://github.com/aamplugin/advanced-access-manager/issues/167
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Backend_Feature_Main_Metabox extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_metaboxes';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/metabox.php';

    /**
     * Get the complete list of admin screens AAM uses to index metaboxes
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    protected function get_screen_urls()
    {
        global $wp_post_types;

        $result = [];

        foreach (array_keys($wp_post_types) as $type) {
            if ($wp_post_types[$type]->show_ui) {
                $result[] = esc_url(add_query_arg(
                    'init',
                    'metabox',
                    admin_url('post-new.php?post_type=' . $type)
                ));
            }
        }

        return $result;
    }

    /**
     * Register metabox service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'           => 'metabox',
            'position'      => 10,
            'title'         => __('Metaboxes', AAM_KEY),
            'capability'    => self::ACCESS_CAPABILITY,
            'type'          => 'main',
            'view'          => __CLASS__,
            'access_levels' => array(
                AAM_Framework_Type_AccessLevel::ROLE,
                AAM_Framework_Type_AccessLevel::USER,
                AAM_Framework_Type_AccessLevel::ALL
            )
        ));
    }

}