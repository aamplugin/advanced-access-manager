<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend core settings
 *
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/270
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Backend_Feature_Settings_Core extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'settings/core.php';

    /**
     * Get list of core options
     *
     * @return array
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/270
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.10
     */
    public static function getList()
    {
        $settings = array(
            'core.settings.editCapabilities' => array(
                'title'       => __('Edit/Delete Capabilities', AAM_KEY),
                'description' => AAM_Backend_View_Helper::preparePhrase('Allow to edit or delete any existing capability on the Capabilities tab. [Warning!] For experienced users only. Changing or deleting capability may result in loosing access to some features or even the entire website.', 'b'),
                'value'       => AAM_Core_Config::get('core.settings.editCapabilities', true)
            ),
            'ui.settings.renderAccessMetabox' => array(
                'title'       => __('Render Access Manager Metabox', AAM_KEY),
                'description' => __('Render "Access Manager" metabox on all post, term or user edit pages.', AAM_KEY),
                'value'       => AAM_Core_Config::get('ui.settings.renderAccessMetabox', false),
            ),
            'core.settings.multiSubject' => array(
                'title'       => __('Multiple Roles Support', AAM_KEY),
                'description' => sprintf(__('Enable support for multiple roles per use. The final access settings will be combined based on the merging preferences. For more information refer to %sMultiple Roles Support%s page.', AAM_KEY), '<a href="https://aamportal.com/plugin/advanced-access-manager/setting/multi-role-support">', '</a>'),
                'value'       => AAM_Core_Config::get('core.settings.multiSubject', false)
            )
        );

        return apply_filters('aam_settings_list_filter', $settings, 'core');
    }

    /**
     * Register core settings UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'settings-core',
            'position'   => 5,
            'title'      => __('Core Settings', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}