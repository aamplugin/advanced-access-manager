<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Multisite service
 *
 * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/389
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/328
 * @since 6.7.5  https://github.com/aamplugin/advanced-access-manager/issues/170
 * @since 6.4.2  https://github.com/aamplugin/advanced-access-manager/issues/81
 * @since 6.3.0  Rewrote the way options are synced across the network
 * @since 6.2.2  Fixed the bug where reset settings was not synced across all sites
 * @since 6.2.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.32
 */
class AAM_Service_Multisite
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.2.0
     */
    const FEATURE_FLAG = 'core.service.multisite.enabled';

    /**
     * Default configurations
     *
     * @version 6.9.34
     */
    const DEFAULT_CONFIG = [
        'core.service.multisite.enabled' => true,
        'multisite.settings.sync'        => true,
        'multisite.settings.nonmember'   => false,
        'multisite.sync.exclude.blogs'   => []
    ];

    /**
     * Syncing flag
     *
     * Preventing from any unexpected loops
     *
     * @var boolean
     *
     * @access protected
     * @version 6.3.0
     */
    protected $syncing = false;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/389
     * @since 6.2.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.32
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);


        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Multisite Support', AAM_KEY),
                    'description' => __('Additional features to support WordPress multisite setup', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);

            if ($enabled && is_multisite()) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Settings_Multisite::register();
                });
            }
        }

        if ($enabled && is_multisite()) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize multisite sync hooks
     *
     * @return void
     *
     * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/389
     * @since 6.7.5  https://github.com/aamplugin/advanced-access-manager/issues/170
     * @since 6.4.2  https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.3.0  Optimized for Multisite setup
     * @since 6.2.2  Hooks to the setting clearing and policy table list
     * @since 6.2.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.32
     */
    protected function initializeHooks()
    {
        if (is_main_site()) {
            // Any changes to the user_roles option should be replicated
            add_action('update_option_' . wp_roles()->role_key, function($old, $value) {
                $this->syncUpdatedOption('%suser_roles', $value);
            }, 10, 2);

            add_action('update_option', function($option, $old, $value) {
                if ($this->syncing === false) {
                    $this->syncing = true;
                    $list = array(
                        AAM_Framework_Service_Configs::DB_OPTION,
                        AAM_Framework_Service_Configs::DB_CONFIGPRESS_OPTION,
                        AAM_Framework_Service_Settings::DB_OPTION
                    );

                    if (in_array($option, $list, true)) {
                        $this->syncUpdatedOption($option, $value);
                    }
                    $this->syncing = false;
                }
            }, 10, 3);

            if (AAM_Framework_Manager::configs()->get_config(
                'multisite.settings.sync'
            )) {
                add_action('aam_top_right_column_action', function() {
                    echo AAM_Backend_View::loadPartial(
                        'multisite-sync-notification'
                    );
                });
            }

            add_action('add_option', function($option, $value) {
                if ($this->syncing === false) {
                    $this->syncing = true;
                    $list = array(
                        AAM_Framework_Service_Configs::DB_OPTION,
                        AAM_Framework_Service_Configs::DB_CONFIGPRESS_OPTION,
                        AAM_Framework_Service_Settings::DB_OPTION
                    );

                    if (in_array($option, $list, true)) {
                        $this->syncUpdatedOption($option, $value);
                    }
                    $this->syncing = false;
                }
            }, 10, 2);

            add_action('aam_clear_settings_action', function($options) {
                foreach($options as $option) {
                    $this->syncDeletedOption($option);
                }
            }, PHP_INT_MAX);
        }

        add_filter('wp_insert_post_data', function($data) {
            if (
                isset($data['post_type'])
                && ($data['post_type'] === AAM_Service_AccessPolicy::POLICY_CPT)
            ) {
                switch_to_blog(AAM_Core_API::getMainSiteId());
            }

            return $data;
        });

        add_action('aam_pre_policy_fetch_action', function() {
            switch_to_blog(AAM_Core_API::getMainSiteId());
        });

        add_action('aam_post_policy_fetch_action', function() {
            restore_current_blog();
        });

        add_action('wp', function() {
            $restricted = false;

            // Check if the non-member access restriction is on
            if (AAM_Framework_Manager::configs()->get_config(
                'multisite.settings.nonmember'
            )) {
                $restricted = !is_user_member_of_blog();
            }

            // Additionally, check if any access policies defined that restrict
            // current site
            $restricted = apply_filters('aam_site_restricted_filter', $restricted);

            // If user is restricted, deny access
            if ($restricted) {
                wp_die('Access Denied', 'aam_access_denied');
            }
        }, 999);
    }

    /**
     * Sync option across all sites
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return void
     *
     * @since 6.2.2 Refactored how the list of sites is fetched
     * @since 6.2.0 Initial implementation of the method
     *
     * @global WPDB $wpdb
     *
     * @access protected
     *
     * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/328
     * @since 6.2.2  Initial implementation of the method
     *
     * @version 6.9.18
     */
    protected function syncUpdatedOption($option, $value)
    {
        global $wpdb;

        foreach($this->getSitList() as $site) {
            if ($this->isSyncDisabled($site->blog_id) !== true) {
                AAM_Core_API::updateOption(
                    str_replace('%s', $wpdb->get_blog_prefix($site->blog_id), $option),
                    $value,
                    true,
                    $site->blog_id
                );
            }
        }
    }

    /**
     * Sync deleted option across all sites
     *
     * @param string $option
     *
     * @return void
     *
     * @access protected
     * @global WPDB $wpdb
     * @version 6.3.0
     */
    protected function syncDeletedOption($option)
    {
        global $wpdb;

        foreach($this->getSitList() as $site) {
            if ($this->isSyncDisabled($site->blog_id) !== true) {
                AAM_Core_API::deleteOption(
                    str_replace('%s', $wpdb->get_blog_prefix($site->blog_id), $option),
                    $site->blog_id
                );
            }
        }
    }

    /**
     * Get list of sites
     *
     * @return array
     *
     * @access protected
     * @version 6.2.2
     */
    protected function getSitList()
    {
        return get_sites(array(
            'number'       => PHP_INT_MAX,
            'offset'       => 0,
            'orderby'      => 'id',
            'site__not_in' => array_merge(
                $this->getExcludedBlogs(), array(get_current_blog_id())
            )
        ));
    }

    /**
     * Get the list of excluded blogs from sync process
     *
     * @return array
     *
     * @access protected
     * @version 6.2.0
     */
    protected function getExcludedBlogs()
    {
        $excluded = array();
        $config   = AAM::api()->configs()->get_config(
            'multisite.sync.exclude.blogs'
        );

        if (is_string($config)) {
            $excluded = explode(',', $config);
        } elseif (is_array($config)) {
            $excluded = array_filter($config, function($site) {
                return is_numeric($site);
            });
        }

        return $excluded;
    }

    /**
     * Check if blog has sync service disabled
     *
     * @param int $blog_id
     *
     * @return boolean
     *
     * @access protected
     * @version 6.3.0
     */
    protected function isSyncDisabled($blog_id)
    {
        $config = AAM_Core_API::getOption(
            AAM_Framework_Service_Configs::DB_OPTION, array(), $blog_id
        );

        return isset($config[self::FEATURE_FLAG])
                    && ($config[self::FEATURE_FLAG] === false);
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Multisite::bootstrap();
}