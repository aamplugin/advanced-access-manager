<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Misc implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Handle framework error
     *
     * @param Exception $exception
     * @param array     $settings
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.1
     */
    public function handle_error($exception, $settings = [])
    {
        $response = null;

        // Determine what is the proper error handling strategy to pick
        if (!empty($settings['error_handling'])) {
            $strategy = $settings['error_handling'];
        } else {
            // Do not rely on WP_DEBUG as many website owners forget to turn off
            // debug mode in production
            $strategy = 'wp_trigger_error';
        }

        if ($strategy === 'exception') {
            throw $exception;
        } elseif ($strategy === 'wp_error') {
            $response = new WP_Error('error', $exception->getMessage());
        } elseif (function_exists('wp_trigger_error')) {
            wp_trigger_error(static::class, $exception->getMessage());
        } else {
            trigger_error(sprintf(
                '%s(): %s', static::class, $exception->getMessage()
            ), E_USER_NOTICE);
        }

        return $response;
    }

    /**
     * Confirm that provided value is base64 encoded string
     *
     * @param string $str
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_base64_encoded($str)
    {
        $result = false;

        // Check if the string is valid base64 by matching with base64 pattern
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) {
            $result = base64_encode(base64_decode($str, true)) === $str;
        }

        return $result;
    }

    /**
     * Sanitize slug
     *
     * Replace any characters that are not alpha-numeric or underscore with "_".
     *
     * @param string $slug
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function sanitize_slug($slug)
    {
        return apply_filters(
            'aam_sanitize_slug',
            strtolower(preg_replace('/[^a-z_\d]/i', '_', $slug)),
            $slug
        );
    }

    /**
     * Convert a callback into a slug
     *
     * @param mixed $callback
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function callable_to_slug($callback)
    {
        $result = '';

        if (is_string($callback)) {
            // If it's a simple function name or string callable
            $result = $callback;
        } elseif (is_array($callback) && count($callback) === 2) {
            // If callable is an array
            list($class, $method) = $callback;

            if (is_a($class, WP_Widget::class)) {
                // The WP_Widget has always the same display_callback method for all
                // widgets
                $result = get_class($class);
            } elseif (is_object($class)) {
                // If the first element is an object, get its class
                $result = get_class($class) . '_' . $method;
            } elseif (is_string($class)) {
                // If the first element is a class name
                $result = $class . '_' . $method;
            }
        }

        // Making the slug a bit prettier
        return $this->sanitize_slug(str_replace('::', '_', $result));
    }

    /**
     * Validate and sanitize URL
     *
     * @param string $url
     *
     * @return bool|string
     * @access private
     *
     * @version 7.0.0
     */
    public function sanitize_url($url)
    {
        $result     = false;
        $parsed_url = $this->parse_url($url);

        if ($parsed_url !== false) {
            // Compile back the URL as following:
            //   - If URL belongs to the same host as site runs on, take only the
            //     relative path
            //   - If URL belongs to a different host, return the absolute path,
            //     however, this may result in failure to validate this URL with
            //     WP core function `wp_validate_redirect` if host is not whitelisted
            $parsed_site_url = $this->parse_url(site_url());

            if ($parsed_url['domain'] === $parsed_site_url['domain']) {
                $result = $parsed_url['relative'];
            } else {
                $result = $parsed_url['absolute'];
            }

            // If URL has domain (host), also validating that is it safe
            if (!empty($parsed_url['domain'])) {
                // Making sure that URL is allowed. Do not use wp_validate_redirect
                // to avoid adding unnecessary stuff to the URL
                $parsed_home_url = $this->parse_url(home_url());
                $allowed_hosts   = (array) apply_filters(
                    'allowed_redirect_hosts',
                    [ $parsed_home_url['domain'] ],
                    isset($parsed_url['domain']) ? $parsed_url['domain'] : ''
                );

                // Finally verifying that URL is safe
                if (!in_array($parsed_url['domain'], $allowed_hosts, true)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * Parse given URL
     *
     * This method attempts to parse given URL and return back only essential
     * attributes
     *
     * @param string $url
     *
     * @return array|bool
     * @access public
     *
     * @version 7.0.6
     */
    public function parse_url($url)
    {
        $result = false;
        $parsed = wp_parse_url(call_user_func(
            function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower',
            is_string($url) ? htmlspecialchars_decode(rtrim($url,  '/')) : ''
        ));

        if ($parsed !== false) {
            // Compile domain
            if (!empty($parsed['host'])) {
                $domain  = $parsed['host'];
                $domain .= !empty($parsed['port']) ? "::{$parsed['port']}" : '';
            } else {
                $domain = '';
            }

            // Compile relative path
            if (empty($parsed['path']) || $parsed['path'] === '/') {
                $path = '/';
            } else {
                // Remove trailing forward slash
                $path = rtrim($parsed['path'], '/');
            }

            // Adding query params if provided
            if (isset($parsed['query'])) {
                // Parse all query params and sort them in alphabetical order
                parse_str($parsed['query'], $query_params);
                ksort($query_params);

                // Finally adding sorted query params to the URL
                $relative = add_query_arg($query_params, $path);
            } else {
                $query_params = [];
                $relative     = $path;
            }

            $result = [
                'domain'   => $domain,
                'relative' => $relative,
                'path'     => $path,
                'params'   => $query_params,
                'absolute' => $domain . $relative
            ];
        }

        return $result;
    }

    /**
     * Get current post
     *
     * @return WP_Post|null
     * @access public
     *
     * @version 7.0.6
     */
    public function get_current_post()
    {
        global $wp_query, $post;

        $res = $post;

        if ($wp_query->is_post_type_archive) {
            // Getting a slug of a browsing page and try to fetch a page
            // by the slug
            $res = get_page_by_path(trim($this->get($_SERVER, 'REQUEST_URI'), '/'));
        } elseif (!empty($wp_query->queried_object)) {
            $res = $wp_query->queried_object;
        } elseif (!empty($wp_query->queried_object_id)) {
            $res = get_post($wp_query->queried_object_id);
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
                $res = $this->get_post_by_slug(
                    $wp_query->query['name'],
                    $wp_query->query['post_type']
                );
            }
        } elseif (isset($_GET['post'])) {
            $res = get_post(filter_input(INPUT_GET, 'post', FILTER_VALIDATE_INT));
        } elseif (isset($_POST['post_ID'])) {
            $res = get_post(filter_input(INPUT_POST, 'post_ID', FILTER_VALIDATE_INT));
        } elseif (!empty($wp_query->post)) {
            $res = $wp_query->post;
        } elseif (get_the_ID()) {
            $res = get_post(get_the_ID());
        }

        if (is_a($res, 'WP_Post')) {
            $result = $res;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get a single post by slug
     *
     * @param string $slug
     * @param string $post_type [Optional]
     *
     * @return WP_Post|null
     * @access public
     *
     * @version 7.0.6
     */
    public function get_post_by_slug($slug, $post_type = 'page')
    {
        $results = get_posts([
            'post_name__in'  => [ $slug ],
            'post_type'      => $post_type,
            'posts_per_page' => 1
        ]);

        return !empty($results[0]) ? $results[0] : null;
    }

    /**
     * Merge two sets of access permissions
     *
     * @param array  $incoming
     * @param array  $base
     * @param string $resource_type
     *
     * @return array
     * @access public
     *
     * @version 7.0.1
     */
    public function merge_permissions($incoming, $base, $resource_type)
    {
        $result = [];

        // Determine the access controls merging preference
        $config     = AAM_Framework_Manager::_()->config;
        $preference = $config->get(
            'core.settings.' . $resource_type . '.merge.preference',
            $config->get('core.settings.merge.preference') // Default merging pref
        );

        // First get the complete list of unique keys
        $permission_keys = array_unique(array_merge(
            array_keys($incoming),
            array_keys($base)
        ));

        foreach($permission_keys as $permission_key) {
            $result[$permission_key] = self::_merge_permissions(
                isset($base[$permission_key]) ? $base[$permission_key] : null,
                isset($incoming[$permission_key]) ? $incoming[$permission_key] : null,
                $preference
            );
        }

        return $result;
    }

    /**
     * Merge to sets of preferences
     *
     * @param array $base
     * @param array $incoming
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function merge_preferences($base, $incoming)
    {
        return array_replace($incoming, $base);
    }

    /**
     * Safely get value from the source
     *
     * @param mixed  $source
     * @param string $xpath
     * @param mixed  $default
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get($source, $xpath, $default = null)
    {
        $value = $source;
        $found = true;

        // Do we need to parse the xpath? It is possible that the xpath was already
        // parsed
        $parsed = is_array($xpath) ? $xpath : $this->_parse_xpath($xpath);

        foreach($parsed as $l) {
            if (is_object($value)) {
                if (property_exists($value, $l)) {
                    $value = $value->{$l};
                } elseif (method_exists($value, $l)) {
                    $value = $value->$l();
                } else {
                    $found = false;
                    break;
                }
            } else if (is_array($value)) {
                if (array_key_exists($l, $value)) {
                    $value = $value[$l];
                } else {
                    $found = false;
                    break;
                }
            }
        }

        return $found ? $value : $default;
    }

    /**
     * Parse xpath string into array
     *
     * @param string $xpath
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _parse_xpath($xpath)
    {
        $result = trim(
            str_replace(
                array('["', '[', '"]', ']', '..'), '.', $xpath
            ),
            ' .' // white space is important!
        );

        return explode('.', $result);
    }

    /**
     * Merge two rules based on provided preference
     *
     * @param array|null $base
     * @param array|null $incoming
     * @param string     $preference
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _merge_permissions($base, $incoming, $preference)
    {
        $result   = null;
        $effect_a = null;
        $effect_b = null;

        if (!empty($base)) {
            $effect_a = $base['effect'] !== 'deny';
        }

        if (!empty($incoming)) {
            $effect_b = $incoming['effect'] !== 'deny';
        }

        if ($preference === 'allow') { // Merging preference is to allow
            // If at least one set has allowed rule, then allow the URL
            if ($effect_a === true) {
                $result = $base;
            } elseif ($effect_b === true) {
                $result = $incoming;
            } elseif ($effect_a === false && $effect_b === false) {
                $result = $base;
            } else {
                $result = [ 'effect' => 'allow' ];
            }
        } else { // Merging preference is to deny access by default
            if ($effect_a === false) {
                $result = $base;
            } elseif ($effect_b === false) {
                $result = $incoming;
            } elseif (!empty($base)) {
                $result = $base;
            } elseif (!empty($incoming)) {
                $result = $incoming;
            } else {
                $result = [ 'effect' => 'allow' ];
            }
        }

        return $result;
    }

    /**
     * Get currently viewed website area
     *
     * @return string
     * @access public
     *
     *
     * @version 7.0.1
     */
    public function get_current_area()
    {
        if (is_admin()) {
            $result = 'backend';
        } elseif ($this->_is_rest_endpoint()) {
            $result = 'api';
        } else {
            $result = 'frontend';
        }

        return $result;
    }

    /**
     * Determine if given user is super admin
     *
     * The super admin user is determined by the following conditions:
     * - If multi-site, user has to be listed in the site option "site_admins"
     * - User has to have the administrator role OR user's email matches the
     *   "admin_email" in the _options table
     *
     * @param int $user_id
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_super_admin($user_id = null)
    {
        $result = false;
        $user   = empty($user_id) ? wp_get_current_user() : get_userdata($user_id);

        if (is_a($user, WP_User::class) && $user->exists()) {
            // Determine if current user is natively a super admin.
            // Note! We are deviating from WP core definition of super admin when it
            // is not a multi-site. We believe the capability delete_users does not
            // define anyone as super admin
            if (is_multisite()) {
                $super_admins = get_super_admins();

                if (is_array($super_admins)
                    && in_array($user->user_login, $super_admins, true)
                ) {
                    $result = true;
                }
            } elseif (in_array('administrator', $user->roles, true)
                || get_option('admin_email') === $user->user_email
            ) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Determine if given user is an administrator
     *
     * @param int $user_id
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.2
     */
    public function is_admin($user_id = null)
    {
        $result = false;
        $user   = empty($user_id) ? wp_get_current_user() : get_userdata($user_id);

        if (is_a($user, WP_User::class) && $user->exists()) {
            $result = in_array('administrator', $user->roles, true);
        }

        return $result;
    }

    /**
     * Convert term slug or ID to WP_Term
     *
     * This method exists because WP core function get_terms does not pass
     * suppress_filters flag to query
     *
     * @param string $slug
     * @param string $taxonomy
     *
     * @return int
     * @access public
     *
     * @version 7.0.0
     */
    public function get_term_by_slug($slug, $taxonomy)
    {
        static $cache = []; global $wpdb;

        $cache_key = "{$slug}_{$taxonomy}";

        if (!array_key_exists($cache_key, $cache)) {
            $query = $wpdb->prepare('SELECT t.term_id FROM ' . $wpdb->terms . ' AS t
                INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
                ON t.term_id = tt.term_id
                WHERE tt.taxonomy = %s AND t.slug = %s', $taxonomy, $slug);

            $cache[$cache_key] = get_term($wpdb->get_var($query), $taxonomy);
        }

        return $cache[$cache_key];
    }

    /**
     * Check if REST request is processed
     *
     * Keep it compatible with older WP versions
     *
     * @return bool
     * @access private
     *
     * @version 7.0.1
     */
    private function _is_rest_endpoint()
    {
	    global $wp_rest_server;

        if (function_exists('wp_is_rest_endpoint')) {
            $result = wp_is_rest_endpoint();
        } else {
            $result = defined('REST_REQUEST') && REST_REQUEST;

            if (!$result && is_a($wp_rest_server, WP_REST_Server::class)) {
                $result = $wp_rest_server->is_dispatching();
            }

            $result = apply_filters('wp_is_rest_endpoint', $result);
        }

        return $result;
    }

}