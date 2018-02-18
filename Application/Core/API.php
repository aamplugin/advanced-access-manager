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
            if (is_null($blog_id)) {
                $blog = get_current_blog_id();
            } elseif ($blog_id == 'site') {
                $blog = (defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1);
            } else {
                $blog = $blog_id;
            }
            $response = get_blog_option($blog, $option, $default);
        } else {
            $response = get_option($option, $default);
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
            } elseif ($blog_id == 'site') {
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
     * @param bool $send_cookies Wheather send cookies or not
     * 
     * @return WP_Error|array
     * 
     * @access public
     */
    public static function cURL($url, $send_cookies = TRUE, $params = array(), $timeout = 20) {
        $header = array('User-Agent' => AAM_Core_Request::server('HTTP_USER_AGENT'));

        $cookies = AAM_Core_Request::cookie(null, array());
        $requestCookies = array();
        if (is_array($cookies) && $send_cookies) {
            foreach ($cookies as $key => $value) {
                //SKIP PHPSESSID - some servers don't like it for security reason
                if ($key !== session_name()) {
                    $requestCookies[] = new WP_Http_Cookie(array(
                        'name' => $key, 'value' => $value
                    ));
                }
            }
        }

        return wp_remote_request($url, array(
            'headers' => $header,
            'method'  => 'POST',
            'body'    => $params,
            'cookies' => $requestCookies,
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
        $levels = array($default);
        
        if (is_array($caps)) { //WP Error Fix bug report
            foreach($caps as $cap => $granted) {
                if ($granted && preg_match('/^level_([0-9]+)$/i', $cap, $match)) {
                    $levels[] = intval($match[1]);
                }
            }
        }
        
        return max($levels);
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
        $caps = array();
        
        foreach (self::getRoles()->role_objects as $role) {
            if (is_array($role->capabilities)) {
                $caps = array_merge($caps, $role->capabilities);
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
        $caps = self::getAllCapabilities();
        
        return (is_string($cap) && array_key_exists($cap, $caps) ? true : false);
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
        if (AAM_Core_Request::server('REQUEST_METHOD') != 'POST') {
            $object = AAM::getUser()->getObject('redirect');
            $type   = $object->get("{$area}.redirect.type");

            if (!empty($type) && ($type == 'login')) {
                $redirect = add_query_arg(
                        array('aam-redirect' => 'login'), 
                        wp_login_url(AAM_Core_Request::server('REQUEST_URI'))
                );
            } elseif (!empty($type) && ($type != 'default')) {
                $redirect = $object->get("{$area}.redirect.{$type}");
            } else { //ConfigPress setup
                $redirect = AAM_Core_Config::get(
                       "{$area}.access.deny.redirect", __('Access Denied', AAM_KEY)
                );
            }

            do_action('aam-rejected-action', $area, $args);

            self::redirect($redirect, $args);
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
        $path = parse_url($rule);
        if ($path && !empty($path['host'])) {
            wp_redirect($rule, 307);
        } elseif (preg_match('/^[\d]+$/', $rule)) {
            wp_safe_redirect(get_page_link($rule), 307);
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
            $data    = get_plugin_data(dirname(__FILE__) . '/../../aam.php');
            $version = (isset($data['Version']) ? $data['Version'] : null);
        }
        
        return (!empty($version) ? $version : null);
    }
    
    /**
     * Get filtered post list
     * 
     * Return only posts that are restricted to LIST or LIST TO OTHERS for the
     * current user. This function is shared by both frontend and backend
     * 
     * @param WP_Query $query
     * @param string   $area
     * 
     * @return array
     * 
     * @access public
     */
    public static function getFilteredPostList($query, $area = 'frontend') {
        $filtered = array();
        $type     = self::getQueryPostType($query);
        
        if ($type) { 
            if (AAM_Core_Cache::has("{$type}__not_in_{$area}")) {
                $filtered = AAM_Core_Cache::get("{$type}__not_in_{$area}");
            } else { //first initial build
                $posts = get_posts(array(
                    'post_type'   => $type, 
                    'numberposts' => AAM_Core_Config::get('get_post_limit', 500), 
                    'post_status' => 'any'
                ));

                foreach ($posts as $post) {
                    if (self::isHiddenPost($post, $type, $area)) {
                        $filtered[] = $post->ID;
                    }
                }
            }
        }
        
        if (is_single()) {
            $post = self::getCurrentPost();
            $in   = ($post ? array_search($post->ID, $filtered) : false);
            
            if ($in !== false) {
                $filtered = array_splice($filtered, $in, 1);
            }
        }
        
        return (is_array($filtered) ? $filtered : array());
    }
    
    /**
     * Check if post is hidden
     * 
     * @param mixed  $post
     * @param string $area
     * 
     * @return boolean
     * 
     * @access public
     */
    public static function isHiddenPost($post, $type, $area = 'frontend') {
        static $counter = 0;
        
        $hidden = false;
        
        if ($counter <= AAM_Core_Config::get('get_post_limit', 500)) { //avoid server crash
            $user    = get_current_user_id();
            $key     = "{$type}__not_in_{$area}";
            $cache   = AAM_Core_Cache::get($key, array());
            $checked = AAM_Core_Cache::get($key . '_checked', array());
            
            if (!in_array($post->ID, $cache)) {
                if (!in_array($post->ID, $checked)) {
                    $object    = AAM::getUser()->getObject('post', $post->ID, $post);
                    $list      = $object->has("{$area}.list");
                    $others    = $object->has("{$area}.list_others");
                    $checked[] = $post->ID;

                    if ($list || ($others && ($post->post_author != $user))) {
                        $hidden  = true;
                        $cache[] = $post->ID;
                    }
                    
                    AAM_Core_Cache::set($key . '_checked', $checked);
                    AAM_Core_Cache::set($key, $cache);
                    $counter++;
                }
            } else {
                $hidden = true;
            }
        }
        
        return $hidden;
    }
    
    /**
     * Get Query post type
     * 
     * @param WP_Query $query
     * 
     * @return string
     * 
     * @access public
     */
    public static function getQueryPostType($query) {
        //get post type based on queired object
        if (!empty($query->query['post_type'])) {
            $type = $query->query['post_type'];
        } elseif (!empty($query->query_vars['post_type'])) {
            $type = $query->query_vars['post_type'];
        }
        
        if (empty($type) || !is_scalar($type)){
            $type = 'post';
        }
        
        return $type;
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
        
        $res = null;
        
        if (!empty($wp_query->queried_object)) {
            $res = $wp_query->queried_object;
        } elseif (!empty($wp_query->post)) {
            $res = $wp_query->post;
        } elseif (!empty($wp_query->query['name']) && !empty($wp_query->posts)) {
            //Important! Cover the scenario of NOT LIST but ALLOW READ
            foreach($wp_query->posts as $post) {
                if ($post->post_name == $wp_query->query['name']) {
                    $res = $post;
                    break;
                }
            }
        }
        
        $user = AAM::getUser();
        
        return (is_a($res, 'WP_Post') ? $user->getObject('post', $res->ID) : null);
    }
    
}