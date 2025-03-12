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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_Core extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'settings/core.php';

    /**
     * Get list of core options
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function getList()
    {
        $config   = AAM::api()->config;
        $settings = array(
            'service.capability.edit_caps' => array(
                'title'       => __('Edit/Delete Capabilities', 'advanced-access-manager'),
                'description' => AAM_Backend_View_Helper::preparePhrase('Allow to edit or delete any existing capability on the Capabilities tab. [Warning!] For experienced users only. Changing or deleting capability may result in loosing access to some features or even the entire website.', 'b'),
                'value'       => $config->get('service.capability.edit_caps')
            ),
            'core.settings.ui.render_access_metabox' => array(
                'title'       => __('Render Access Manager Metabox', 'advanced-access-manager'),
                'description' => __('Render "Access Manager" metabox on all post, term or user edit pages.', 'advanced-access-manager'),
                'value'       => $config->get('core.settings.ui.render_access_metabox'),
            ),
            'core.settings.ui.tips' => array(
                'title'       => __('Show UI Tooltips', 'advanced-access-manager'),
                'description' => __('Display helpful tooltips and notifications on the AAM UI page to educate about existing functionality.', 'advanced-access-manager'),
                'value'       => $config->get('core.settings.ui.tips')
            ),
            'core.settings.multi_access_levels' => array(
                'title'       => __('Multiple Roles Support', 'advanced-access-manager'),
                'description' => sprintf(__('Enable support for multiple roles per use. The final access settings will be combined based on the merging preferences. For more information refer to %sMultiple Roles Support%s page.', 'advanced-access-manager'), '<a href="https://aamportal.com/reference/advanced-access-manager/setting/multi-role-support?ref=plugin">', '</a>'),
                'value'       => $config->get('core.settings.multi_access_levels')
            ),
            'core.settings.merge.preference' => array(
                'title'       => __('Default Access Settings Merging Preference', 'advanced-access-manager'),
                'description' => sprintf(__('Default access settings merging preference when settings ambiguity detected. For more information refer to the %sResolving access control ambiguity in WordPress%s article.', 'advanced-access-manager'), '<a href="https://aamportal.com/article/resolving-access-controls-ambiguity-in-wordpress?ref=plugin" target="_blank">', '</a>'),
                'value'       => $config->get('core.settings.merge.preference') === 'allow',
                'valueOn'     => 'allow',
                'valueOff'    => 'deny',
                'optionOn'    => __('Allow', 'advanced-access-manager'),
                'optionOff'   => __('Deny', 'advanced-access-manager')
            ),
            'core.settings.xmlrpc_enabled' => array(
                'title'       => __('XML-RPC WordPress API', 'advanced-access-manager'),
                'description' => sprintf(__('Remote procedure call (RPC) interface is used to manage WordPress website content and features. For more information check %sXML-RPC Support%s article.', 'advanced-access-manager'), '<a href="https://codex.wordpress.org/XML-RPC_Support">', '</a>'),
                'value'       => $config->get('core.settings.xmlrpc_enabled')
            ),
            'core.settings.restful_enabled' => array(
                'title'       => __('RESTful WordPress API', 'advanced-access-manager'),
                'description' => sprintf(__('The RESTful interface is used to manage WordPress website content and features. For detail, refer to %sREST API handbook%s.', 'advanced-access-manager'), '<a href="https://developer.wordpress.org/rest-api/">', '</a>'),
                'value'       => $config->get('core.settings.restful_enabled')
            )
        );

        return apply_filters('aam_settings_list_filter', $settings, 'core');
    }

    /**
     * Register core settings UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'settings-core',
            'position'   => 5,
            'title'      => __('Core Settings', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}