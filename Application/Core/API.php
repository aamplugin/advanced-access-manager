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
 * @since 6.2.2 Minor refactoring to the clearSettings method
 * @since 6.0.5 Fixed bug with getOption method where incorrect type could be
 *              returned
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.2
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
     * @since 6.0.5 Fixed the bug where option may not be returned as proper type
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.5
     */
    public static function getOption($option, $default = null, $blog_id = null)
    {
        if (is_multisite()) {
            if (is_null($blog_id) || get_current_blog_id() === $blog_id) {
                $response = self::getCachedOption($option, $default);
            } else {
                if ($blog_id === 'site') {
                    $blog = (defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1);
                } else {
                    $blog = $blog_id;
                }
                $response = get_blog_option($blog, $option, $default);
            }
        } else {
            $response = self::getCachedOption($option, $default);
        }

        return maybe_unserialize($response);
    }

    /**
     * Get cached option
     *
     * This reduces the number of DB queries
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getCachedOption($option, $default)
    {
        $response = $default;
        $cache    = wp_cache_get('alloptions', 'options');

        if (empty($cache)) {
            $response = get_option($option, $default);
        } elseif(isset($cache[$option])) {
            $response = maybe_unserialize($cache[$option]);
        }

        return $response;
    }

    /**
     * Update option in the DB
     *
     * @param string $option
     * @param mixed  $data
     * @param int    $blog_id
     *
     * @return bool
     *
     * @access public
     * @version 6.0.0
     */
    public static function updateOption(
        $option, $data, $blog_id = null, $autoload = null
    ) {
        if (is_multisite()) {
            if (is_null($blog_id)) {
                $blog = get_current_blog_id();
            } elseif ($blog_id === 'site') {
                $blog = (defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1);
            } else {
                $blog = $blog_id;
            }
            $response = update_blog_option($blog, $option, $data);
        } else {
            $response = update_option($option, $data, $autoload);
        }

        return $response;
    }

    /**
     * Delete option from the DB
     *
     * @param string $option
     * @param int    $blog_id
     *
     * @return bool
     *
     * @access public
     * @version 6.0.0
     */
    public static function deleteOption($option, $blog_id = null)
    {
        if (is_multisite()) {
            if (is_null($blog_id)) {
                $blog = get_current_blog_id();
            } elseif ($blog_id == 'site') {
                $blog = (defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1);
            } else {
                $blog = $blog_id;
            }
            $response = delete_blog_option($blog, $option);
        } else {
            $response = delete_option($option);
        }

        return $response;
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
     * Return max user level
     *
     * @param array $caps
     * @param int   $default
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public static function maxLevel($caps, $default = 0)
    {
        $max = $default;

        if (is_array($caps)) { // WP Error Fix bug report
            foreach ($caps as $cap => $granted) {
                if (!empty($granted) && preg_match('/^level_([0-9]+)$/', $cap, $match)) {
                    $max = ($max < $match[1] ? $match[1] : $max);
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
     * @since 6.2.2 Refactored the way we iterate over the deleting list of options
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.2
     */
    public static function clearSettings()
    {
        $options = array(
            AAM_Core_AccessSettings::DB_OPTION,
            AAM_Core_Config::DB_OPTION,
            AAM_Core_ConfigPress::DB_OPTION,
            AAM_Core_Migration::DB_OPTION
        );

        foreach($options as $option) {
            self::deleteOption($option);
        }

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
     * @access public
     * @version 6.0.0
     */
    public static function getAPIEndpoint()
    {
        $endpoint = getenv('AAM_ENDPOINT');

        return ($endpoint ? $endpoint : 'https://api.aamplugin.com/v2');
    }

}