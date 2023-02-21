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
 * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/170
 * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
 * @since 6.3.0 Rewrote the way options are synced across the network
 * @since 6.2.2 Fixed the bug where reset settings was not synced across all sites
 * @since 6.2.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.5
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
     * @access protected
     * @version 6.2.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Multisite Settings Sync', AAM_KEY),
                    'description' => __('Automatically synchronize changes to the list of roles and capabilities as well as all access settings (if configured accordingly).', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true) && is_multisite()) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize multisite sync hooks
     *
     * @return void
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/170
     * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.3.0 Optimized for Multisite setup
     * @since 6.2.2 Hooks to the setting clearing and policy table list
     * @since 6.2.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.5
     */
    protected function initializeHooks()
    {
        $roles = AAM_Framework_Manager::roles();

        if (is_main_site()) {
            // Any changes to the user_roles option should be replicated
            add_action('update_option_' . $roles->role_key, function($old, $value) {
                $this->syncUpdatedOption('%suser_roles', $value);
            }, 10, 2);

            add_action('update_option', function($option, $old, $value) {
                if ($this->syncing === false) {
                    $this->syncing = true;
                    $list = array(
                        AAM_Core_Config::DB_OPTION,
                        AAM_Core_ConfigPress::DB_OPTION,
                        AAM_Core_AccessSettings::DB_OPTION
                    );

                    if (in_array($option, $list, true)) {
                        $this->syncUpdatedOption($option, $value);
                    }
                    $this->syncing = false;
                }
            }, 10, 3);

            add_action('aam_top_right_column_action', function() {
                echo AAM_Backend_View::loadPartial('multisite-sync-notification');
            });

            add_action('add_option', function($option, $value) {
                if ($this->syncing === false) {
                    $this->syncing = true;
                    $list = array(
                        AAM_Core_Config::DB_OPTION,
                        AAM_Core_ConfigPress::DB_OPTION,
                        AAM_Core_AccessSettings::DB_OPTION
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
            if (apply_filters('aam_allowed_site_filter', true) === false) {
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
     * @access protected
     * @global WPDB $wpdb
     * @version 6.2.2
     */
    protected function syncUpdatedOption($option, $value)
    {
        global $wpdb;

        foreach($this->getSitList() as $site) {
            if ($this->isSyncDisabled($site->blog_id) !== true) {
                AAM_Core_API::updateOption(
                    str_replace('%s', $wpdb->get_blog_prefix($site->blog_id), $option),
                    $value,
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
        $config   = AAM::api()->getConfig('multisite.sync.exclude.blogs', array());

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
            AAM_Core_Config::DB_OPTION, array(), $blog_id
        );

        return isset($config[self::FEATURE_FLAG])
                    && ($config[self::FEATURE_FLAG] === false);
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Multisite::bootstrap();
}