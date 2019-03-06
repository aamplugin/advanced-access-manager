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
 * NOTE! THIS IS LEGACY CLASS THAT SLOWLY WILL DIE! DO NOT RELY ON ITS METHODS
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
final class AAM_Core_API {

    /**
     * Get option
     *
     * @param string $option
     * @param mixed  $default
     * @param int    $blog_id
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getOption($option, $default = FALSE, $blog_id = null) {
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

        return $response;
    }
    
    /**
     * 
     * @staticvar type $xmlrpc
     * @return \classname
     */
    public static function getXMLRPCServer() {
        static $xmlrpc = null;
        
        if (is_null($xmlrpc)) {
            require_once(ABSPATH . WPINC . '/class-IXR.php');
            require_once(ABSPATH . WPINC . '/class-wp-xmlrpc-server.php'); 
            $classname = apply_filters('wp_xmlrpc_server_class', 'wp_xmlrpc_server');
            $xmlrpc = new $classname;
        }
        
        return $xmlrpc;
    }
    
    /**
     * 
     * @param type $option
     * @param type $default
     * @return type
     */
    protected static function getCachedOption($option, $default) {
        $cache = wp_cache_get('alloptions', 'options');
        
        if (empty($cache)) {
            $response = get_option($option, $default);
        } else {
            $response = isset($cache[$option]) ? maybe_unserialize($cache[$option]) : $default;
        }
        
        return $response;
    }

    /**
     * Update option
     *
     * @param string $option
     * @param mixed  $data
     * @param int    $blog_id
     *
     * @return bool
     *
     * @access public
     * @static
     */
    public static function updateOption($option, $data, $blog_id = null) {
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
            $response = update_option($option, $data);
        }

        return $response;
    }

    /**
     * Delete option
     *
     * @param string $option
     * @param int    $blog_id
     * 
     * @return bool
     *
     * @access public
     * @static
     */
    public static function deleteOption($option, $blog_id = null) {
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
     * Initiate HTTP request
     *
     * @param string $url Requested URL
     * 
     * @return WP_Error|array
     * 
     * @access public
     */
    public static function cURL($url, $params = array(), $timeout = 20) {
        $header = array('User-Agent' => AAM_Core_Request::server('HTTP_USER_AGENT'));

        return wp_remote_request($url, array(
            'headers' => $header,
            'method'  => 'POST',
            'body'    => $params,
            'timeout' => $timeout
        ));
    }
    
    /**
     * Get role list
     * 
     * @global WP_Roles $wp_roles
     * 
     * @return WP_Roles
     */
    public static function getRoles() {
        global $wp_roles;
        
        if (function_exists('wp_roles')) {
            $roles = wp_roles();
        } elseif(isset($wp_roles)) {
            $roles = $wp_roles;
        } else {
            $roles = new WP_Roles();
        }
        
        return $roles;
    }
    
    /**
     * Return max capability level
     * 
     * @param array $caps
     * @param int   $default
     * 
     * @return int
     * 
     * @access public
     */
    public static function maxLevel($caps, $default = 0) {
        $max = $default;
        
        if (is_array($caps)) { //WP Error Fix bug report
            foreach($caps as $cap => $granted) {
                if (!empty($granted) && preg_match('/^level_([0-9]+)$/', $cap, $match)) {
                    $max = ($max < $match[1] ? $match[1] : $max);
                }
            }
        }
        
        return intval($max);
    }
    
    /**
     * Get all capabilities
     * 
     * Prepare and return list of all registered in the system capabilities
     * 
     * @return array
     * 
     * @access public
     */
    public static function getAllCapabilities() {
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
     * @static
     */
    public static function capabilityExists($cap) {
        $caps   = self::getAllCapabilities();
        $exists = array_key_exists($cap, $caps) ? true : false;
        
        return (is_string($cap) && $exists);
    }
    
    /**
     * Clear all AAM settings
     * 
     * @global wpdb $wpdb
     * 
     * @access public
     */
    public static function clearSettings() {
        global $wpdb;

        //clear wp_options
        $oquery = "DELETE FROM {$wpdb->options} WHERE (`option_name` LIKE %s) AND ";
        $oquery .= "(`option_name` NOT IN ('aam-extensions', 'aam-uid'))";
        $wpdb->query($wpdb->prepare($oquery, 'aam%'));

        //clear wp_postmeta
        $pquery = "DELETE FROM {$wpdb->postmeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($pquery, 'aam-post-access-%'));

        //clear wp_usermeta
        $uquery = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($uquery, 'aam%'));

        $mquery = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s";
        $wpdb->query($wpdb->prepare($mquery, $wpdb->prefix . 'aam%'));
        
        self::clearCache();
    }
    
    /**
     * 
     * @param AAM_Core_Subject $subject
     */
    public static function clearCache($subject = null) {
        global $wpdb;
        
        if (empty($subject)) { // clear all cache
            // visitors, default and role cache
            $query = "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE %s";
            $wpdb->query($wpdb->prepare($query, '%aam_cache%' ));
            
            // TODO: aam_visitor_cache does not follow the option naming pattern
            $query = "DELETE FROM {$wpdb->options} WHERE `option_name` = %s";
            $wpdb->query($wpdb->prepare($query, 'aam_visitor_cache' ));
            
            // user cache
            $query = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` LIKE %s";
            $wpdb->query($wpdb->prepare($query, '%aam_cache%' ));
        } else {
            //clear visitor cache
            $subject->getObject('cache')->reset();
        }
    }
    
    /**
     * Reject the request
     *
     * Redirect or die the execution based on ConfigPress settings
     * 
     * @param string $area
     * @param array  $args
     *
     * @return void
     *
     * @access public
     */
    public static function reject($area = 'frontend', $args = array()) {
        if (AAM_Core_Request::server('REQUEST_METHOD') !== 'POST') {
            $object = AAM::getUser()->getObject('redirect');
            $type   = $object->get("{$area}.redirect.type");

            if ($type === 'login') {
                $redirect = add_query_arg(
                        array('reason' => 'restricted'), 
                        wp_login_url(AAM_Core_Request::server('REQUEST_URI'))
                );
            } elseif (!empty($type) && ($type !== 'default')) {
                $redirect = $object->get("{$area}.redirect.{$type}");
            } else { //ConfigPress setup
                $redirect = AAM_Core_Config::get(
                    "{$area}.access.deny.redirectRule", __('Access Denied', AAM_KEY)
                );
            }
            
            $doRedirect = true;
            
            if ($type === 'page') {
                $page = self::getCurrentPost();
                $doRedirect = (empty($page) || ($page->ID !== intval($redirect)));
            } elseif ($type === 'url') {
                $doRedirect = strpos($redirect, AAM_Core_Request::server('REQUEST_URI')) === false;
            }
            
            if ($doRedirect) {
                do_action('aam-access-rejected-action', $area, $args);
                self::redirect($redirect, $args);
            }
        } else {
            wp_die(-1);
        }
    }
    
    /**
     * Redirect request
     * 
     * Redirect user based on defined $rule
     * 
     * @param mixed $rule
     * @param mixed $args
     * 
     * @access public
     */
    public static function redirect($rule, $args = null) {
        $path = wp_parse_url($rule);
        
        if ($path && !empty($path['host'])) {
            wp_redirect($rule, 307); exit;
        } elseif (preg_match('/^[\d]+$/', $rule)) {
            wp_safe_redirect(get_page_link($rule), 307); exit;
        } elseif (is_callable($rule)) {
            call_user_func($rule, $args);
        } elseif (!empty($args['callback']) && is_callable($args['callback'])) {
            call_user_func($args['callback'], $rule, '', array());
        } else {
            wp_die($rule);
        }
        exit;
    }
    
    /**
     * Remove directory recursively
     * 
     * @param string $pathname
     * 
     * @return void
     * 
     * @access public
     */
    public static function removeDirectory($pathname) {
        $files = glob($pathname . '/*');
        
	foreach ($files as $file) {
		is_dir($file) ? self::removeDirectory($file) : @unlink($file);
	}
        
	@rmdir($pathname);
    }
    
    /**
     * Get plugin version
     * 
     * @return string
     * 
     * @access public
     */
    public static function version() {
        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (function_exists('get_plugin_data')) {
            $data = get_plugin_data(
                    realpath(dirname(__FILE__) . '/../../aam.php')
            );
            $version = (isset($data['Version']) ? $data['Version'] : null);
        }
        
        return (!empty($version) ? $version : null);
    }
    
    /**
     * Get current post
     * 
     * @global type $wp_query
     * 
     * @return WP_Post|null
     */
    public static function getCurrentPost() {
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
                foreach($wp_query->posts as $p) {
                    if ($p->post_name === $wp_query->query['name']) {
                        $res = $p;
                        break;
                    }
                }
            } elseif (!empty($wp_query->query['post_type'])) {
                $res = get_page_by_path(
                    $wp_query->query['name'], OBJECT, $wp_query->query['post_type']
                );
            }
        }
        
        $user = AAM::getUser();
        
        return (is_a($res, 'WP_Post') ? $user->getObject('post', $res->ID) : null);
    }
    
}