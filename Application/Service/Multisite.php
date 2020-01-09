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
 * @since 6.2.2 Fixed the bug where reset settings was not synced across all sites
 * @since 6.2.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.2
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
     * Previously used blog ID
     *
     * When multisite setup, AAM stores all the policies in the main blog so they
     * can be applied to the entire network.
     *
     * @var int
     *
     * @access protected
     * @version 6.2.0
     */
    protected $switch_back_blog_id = null;

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
     * @since 6.2.2 Hooks to the setting clearing and policy table list
     * @since 6.2.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.2
     */
    protected function initializeHooks()
    {
        $roles = AAM_Core_API::getRoles();

        // Any changes to the user_roles option should be replicated
        add_action('update_option_' . $roles->role_key, function($old_value, $value) {
            $this->syncOption('%suser_roles', $value);
        }, 10, 2);

        // Sync changes to config
        add_action('update_option_' . AAM_Core_Config::DB_OPTION, function($o, $n) {
            $this->syncOption(AAM_Core_Config::DB_OPTION, $n);
        });

        // Sync changes to ConfigPress
        add_action('update_option_' . AAM_Core_ConfigPress::DB_OPTION, function($o, $n) {
            $this->syncOption(AAM_Core_ConfigPress::DB_OPTION, $n);
        });

        add_action('aam_updated_access_settings', function($settings) {
            $this->syncOption(AAM_Core_AccessSettings::DB_OPTION, $settings);
        });

        // Sync settings resetting
        add_action('aam_clear_settings_action', function($options) {
            $this->resetOptions($options);
        });

        add_filter('wp_insert_post_data', function($data) {
            if (
                isset($data['post_type'])
                && ($data['post_type'] === AAM_Service_AccessPolicy::POLICY_CPT)
            ) {
                switch_to_blog(get_main_site_id());
            }

            return $data;
        });

        add_action('aam_pre_policy_fetch_action', function() {
            $this->switch_back_blog_id = get_current_blog_id();
            switch_to_blog(get_main_site_id());
        });

        add_action('aam_post_policy_fetch_action', function() {
            switch_to_blog($this->switch_back_blog_id);
        });

        add_action('wp', function() {
            if (apply_filters('aam_allowed_site_filter', true) === false) {
                wp_die('Access Denied', 'aam_access_denied');
            }
        }, 999);

        add_filter('aam_is_managed_policy_filter', function() {
            return is_main_site();
        });
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
    protected function syncOption($option, $value)
    {
        global $wpdb;

        foreach($this->getSitList() as $site) {
            AAM_Core_API::updateOption(
                str_replace('%s', $wpdb->get_blog_prefix($site->blog_id), $option),
                $value,
                $site->blog_id
            );
        }
    }

    /**
     * Reset settings across all sites
     *
     * @param array $options
     *
     * @return void
     *
     * @access protected
     * @version 6.2.2
     */
    protected function resetOptions($options)
    {
        foreach($this->getSitList() as $site) {
            foreach($options as $option) {
                AAM_Core_API::deleteOption($option, $site->blog_id);
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

}

if (defined('AAM_KEY')) {
    AAM_Service_Multisite::bootstrap();
}