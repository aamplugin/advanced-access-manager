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
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 *               https://github.com/aamplugin/advanced-access-manager/issues/311
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/298
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/270
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.34
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
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
     *               https://github.com/aamplugin/advanced-access-manager/issues/311
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/298
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/270
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public static function getList()
    {
        $service  = AAM_Framework_Manager::configs();
        $settings = array(
            'core.settings.editCapabilities' => array(
                'title'       => __('Edit/Delete Capabilities', AAM_KEY),
                'description' => AAM_Backend_View_Helper::preparePhrase('Allow to edit or delete any existing capability on the Capabilities tab. [Warning!] For experienced users only. Changing or deleting capability may result in loosing access to some features or even the entire website.', 'b'),
                'value'       => $service->get_config('core.settings.editCapabilities')
            ),
            'ui.settings.renderAccessMetabox' => array(
                'title'       => __('Render Access Manager Metabox', AAM_KEY),
                'description' => __('Render "Access Manager" metabox on all post, term or user edit pages.', AAM_KEY),
                'value'       => $service->get_config('ui.settings.renderAccessMetabox'),
            ),
            'core.settings.tips' => array(
                'title'       => __('Show UI Tooltips', AAM_KEY),
                'description' => __('Display helpful tooltips and notifications on the AAM UI page to educate about existing functionality.', AAM_KEY),
                'value'       => $service->get_config('core.settings.tips')
            ),
            'core.settings.multiSubject' => array(
                'title'       => __('Multiple Roles Support', AAM_KEY),
                'description' => sprintf(__('Enable support for multiple roles per use. The final access settings will be combined based on the merging preferences. For more information refer to %sMultiple Roles Support%s page.', AAM_KEY), '<a href="https://aamportal.com/reference/advanced-access-manager/setting/multi-role-support?ref=plugin">', '</a>'),
                'value'       => $service->get_config('core.settings.multiSubject')
            ),
            'core.settings.merge.preference' => array(
                'title'       => __('Default Access Settings Merging Preference', AAM_KEY),
                'description' => sprintf(__('Default access settings merging preference when settings ambiguity detected. For more information refer to the %sResolving access control ambiguity in WordPress%s article.', AAM_KEY), '<a href="https://aamportal.com/article/resolving-access-controls-ambiguity-in-wordpress?ref=plugin" target="_blank">', '</a>'),
                'value'       => $service->get_config('core.settings.merge.preference') === 'allow',
                'valueOn'     => 'allow',
                'valueOff'    => 'deny',
                'optionOn'    => __('Allow', AAM_KEY),
                'optionOff'   => __('Deny', AAM_KEY)
            ),
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