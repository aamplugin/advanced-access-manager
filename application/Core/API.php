<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/382
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/328
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
 * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/243
 * @since 6.7.0  https://github.com/aamplugin/advanced-access-manager/issues/151
 * @since 6.6.4  https://github.com/aamplugin/advanced-access-manager/issues/142
 * @since 6.3.1  Fixed bug with setting clearing
 * @since 6.3.0  Optimized for Multisite setup
 * @since 6.2.2  Minor refactoring to the clearSettings method
 * @since 6.0.5  Fixed bug with getOption method where incorrect type could be
 *               returned
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
final class AAM_Core_API
{

    /**
     * Get option from the database
     *
     * @param string $option
     * @param mixed  $default
     * @param int    $blog_id
     *
     * @return mixed
     *
     * @since 6.3.0 Optimized for Multisite setup
     * @since 6.0.5 Fixed the bug where option may not be returned as proper type
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public static function getOption($option, $default = array(), $blog_id = null)
    {
        if (is_multisite()) {
            $result = get_blog_option(
                ($blog_id ? $blog_id : get_current_blog_id()), $option, $default
            );
        } else {
            $result = get_option($option, $default);
        }

        return $result;
    }

    /**
     * Update option in the DB
     *
     * @param string  $option
     * @param mixed   $data
     * @param boolean $autoload
     * @param int     $blog_id
     *
     * @return bool
     *
     * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/328
     * @since 6.7.0  https://github.com/aamplugin/advanced-access-manager/issues/151
     * @since 6.3.0  Optimized for Multisite setup
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.18
     */
    public static function updateOption(
        $option, $data, $autoload = true, $blog_id = null
    ) {
        $old_value = self::getOption($option, null, $blog_id);

        if (maybe_serialize($old_value) !== maybe_serialize($data)) {
            if (is_multisite()) {
                $result = update_blog_option(
                    ($blog_id ? $blog_id : get_current_blog_id()), $option, $data
                );
            } else {
                $result = update_option($option, $data, $autoload);
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Delete option from the DB
     *
     * @param string $option
     * @param int    $blog_id
     *
     * @return bool
     *
     * @since 6.3.0 Optimized for Multisite setup
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public static function deleteOption($option, $blog_id = null)
    {
        if (is_multisite()) {
            $result = delete_blog_option(
                ($blog_id ? $blog_id : get_current_blog_id()), $option
            );
        } else {
            $result = delete_option($option);
        }

        return $result;
    }

    /**
     * Get role list
     *
     * @global WP_Roles $wp_roles
     *
     * @return WP_Roles
     *
     * @access public
     * @version 6.0.0
     */
    public static function getRoles()
    {
        global $wp_roles;

        if (function_exists('wp_roles')) {
            $roles = wp_roles();
        } elseif (isset($wp_roles)) {
            $roles = $wp_roles;
        } else {
            $roles = new WP_Roles();
        }

        return $roles;
    }

    /**
     * Get main site id
     *
     * Compatibility method for sites that are under WP 4.9.0
     *
     * @return int
     *
     * @access public
     * @version 6.4.2
     */
    public static function getMainSiteId()
    {
        if (function_exists('get_main_site_id')) {
            $id = get_main_site_id();
        } elseif (is_multisite()) {
            $network = get_network();
            $id      = ($network ? $network->site_id : 0);
        } else {
            $id = get_current_blog_id();
        }

        return $id;
    }

    /**
     * Return max user level
     *
     * @param array $caps
     * @param int   $default
     *
     * @return int
     *
     * @since 6.6.4 https://github.com/aamplugin/advanced-access-manager/issues/142
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.4
     */
    public static function maxLevel($caps, $default = 0)
    {
        $max = $default;

        if (is_array($caps)) { // WP Error Fix bug report
            foreach ($caps as $cap => $granted) {
                if (!empty($granted) && (strpos($cap, 'level_') === 0)) {
                    $level = intval(substr($cap, 6));
                    $max   = ($max < $level ? $level : $max);
                }
            }
        }

        return intval($max);
    }

    /**
     * Get list of all capabilities
     *
     * Prepare and return list of all registered in the system capabilities
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public static function getAllCapabilities()
    {
        static $caps = array();

        if (empty($caps)) {
            foreach (self::getRoles()->role_objects as $role) {
                if (is_array($role->capabilities)) {
                    $caps = array_merge($caps, $role->capabilities);
                }
            }
        }

        return $caps;
    }

    /**
     * Check if capability exists
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function capExists($cap)
    {
        // Get list of all capabilities registered on the role levels
        $caps = self::getAllCapabilities();

        // Get list of all capabilities that are assigned on the user level if user
        // is authenticated
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $caps = array_merge($user->caps, $user->allcaps, $caps);
        }

        return (is_string($cap) && array_key_exists($cap, $caps));
    }

    /**
     * Check if AAM capability is allowed
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public static function isAAMCapabilityAllowed($cap)
    {
        return !self::capExists($cap) || current_user_can($cap);
    }

    /**
     * Clear all AAM settings
     *
     * @return void
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/382
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.3.1  https://github.com/aamplugin/advanced-access-manager/issues/48
     * @since 6.2.2  Refactored the way we iterate over the deleting list of options
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public static function clearSettings()
    {
        global $wpdb;

        $options = array(
            AAM_Framework_Service_Settings::DB_OPTION,
            AAM_Framework_Service_Configs::DB_OPTION,
            AAM_Core_Cache::DB_OPTION,
            AAM_Framework_Service_Configs::DB_CONFIGPRESS_OPTION,
            AAM_Service_AdminMenu::CACHE_DB_OPTION,
            AAM_Service_Toolbar::CACHE_DB_OPTION
        );

        foreach($options as $option) {
            self::deleteOption($option);
        }

        // Delete all legacy options
        $query  = "DELETE FROM {$wpdb->options} WHERE (`option_name` LIKE %s) AND ";
        $query .= "(`option_name` NOT IN ('aam_addons', 'aam_migrations'))";
        $wpdb->query($wpdb->prepare($query, 'aam%'));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE `meta_key` LIKE %s", 'aam-%'
        ));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s", 'aam%'
        ));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s",
            $wpdb->get_blog_prefix() . 'aam%'
        ));

        // Trigger the action to inform other services to clean-up the options
        do_action('aam_clear_settings_action', $options);
    }

    /**
     * Get current post
     *
     * @return AAM_Core_Object_Post|null
     *
     * @access public
     * @global WP_Query $wp_query
     * @global WP_Post  $post
     * @version 6.0.0
     */
    public static function getCurrentPost()
    {
        global $wp_query, $post;

        $res = $post;

        if (get_the_ID()) {
            $res = get_post(get_the_ID());
        } elseif (!empty($wp_query->queried_object)) {
            $res = $wp_query->queried_object;
        } elseif (!empty($wp_query->post)) {
            $res = $wp_query->post;
        } elseif (!empty($wp_query->query_vars['p'])) {
            $res = get_post($wp_query->query_vars['p']);
        } elseif (!empty($wp_query->query_vars['page_id'])) {
            $res = get_post($wp_query->query_vars['page_id']);
        } elseif (!empty($wp_query->query['name'])) {
            //Important! Cover the scenario of NOT LIST but ALLOW READ
            if (!empty($wp_query->posts)) {
                foreach ($wp_query->posts as $p) {
                    if ($p->post_name === $wp_query->query['name']) {
                        $res = $p;
                        break;
                    }
                }
            } elseif (!empty($wp_query->query['post_type'])) {
                $res = get_page_by_path(
                    $wp_query->query['name'],
                    OBJECT,
                    $wp_query->query['post_type']
                );
            }
        }

        if (is_a($res, 'WP_Post')) {
            $result = AAM::getUser()->getObject(
                AAM_Core_Object_Post::OBJECT_TYPE, $res->ID
            );
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get WP core password hasher
     *
     * @return PasswordHash
     *
     * @access public
     * @version 6.0.0
     */
    public static function prepareHasher()
    {
        require_once ABSPATH . WPINC . '/class-phpass.php';

        return new PasswordHash(8, true);
    }

    /**
     * Get AAM API endpoint
     *
     * @return string
     *
     * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/243
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.5
     */
    public static function getAPIEndpoint()
    {
        $endpoint = getenv('AAM_ENDPOINT');

        return ($endpoint ? $endpoint : 'https://api.aamportal.com');
    }

}