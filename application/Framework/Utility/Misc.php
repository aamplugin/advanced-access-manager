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
class AAM_Framework_Utility_Misc
{

    /**
     * Confirm that provided value is base64 encoded string
     *
     * @param string $str
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public static function is_base64_encoded($str)
    {
        $result = false;

        // Check if the string is valid base64 by matching with base64 pattern
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) {
            // Decode the string and check if it can be re-encoded to match original
            $decoded = base64_decode($str, true);

            if ($decoded !== false && base64_encode($decoded) === $str) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Validate and sanitize URL
     *
     * @param string $url
     *
     * @return bool|string
     *
     * @access private
     * @version 7.0.0
     */
    public static function sanitize_url($url)
    {
        $result     = false;
        $parsed_url = self::parse_url($url);

        if ($parsed_url !== false) {
            // Compile back the URL as following:
            //   - If URL belongs to the same host as site runs on, take only the
            //     relative path
            //   - If URL belongs to a different host, return the absolute path,
            //     however, this may result in failure to validate this URL with
            //     WP core function `wp_validate_redirect` if host is not whitelisted
            $parsed_site_url = self::parse_url(site_url());

            if ($parsed_url['domain'] === $parsed_site_url['domain']) {
                $result = $parsed_url['relative'];
            } else {
                $result = $parsed_url['absolute'];
            }

            // If URL has domain (host), also validating that is it safe
            if (!empty($parsed_url['domain'])) {
                // Making sure that URL is allowed. Do not use wp_validate_redirect
                // to avoid adding unnecessary stuff to the URL
                $parsed_home_url = self::parse_url(home_url());
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
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function parse_url($url)
    {
        $result = false;
        $parsed = wp_parse_url(call_user_func(
            function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower',
            is_string($url) ? rtrim($url,  '/') : ''
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
            $path = empty($parsed['path']) ? '/' : rtrim($parsed['path'], '/');

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
     * Merge two sets of access permissions
     *
     * @param array  $base
     * @param array  $incoming
     * @param string $resource_type
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public static function merge_permissions($base, $incoming, $resource_type)
    {
        $result = [];
        $config = AAM::api()->configs();

        // If preference is not explicitly defined, fetch it from the AAM configs
        $preference = $config->get_config('core.settings.merge.preference');

        $preference = $config->get_config(
            'core.settings.' . $resource_type . '.merge.preference',
            $preference
        );

        // First get the complete list of unique keys
        $permission_keys = array_unique([
            ...array_keys($incoming),
            ...array_keys($base)
        ]);

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
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function merge_preferences($base, $incoming)
    {
        return array_replace($incoming, $base);
    }

    /**
     * Merge two rules based on provided preference
     *
     * @param array|null $base
     * @param array|null $incoming
     * @param string     $preference
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private static function _merge_permissions(
        $base, $incoming, $preference = 'deny'
    ) {
        $result   = null;
        $effect_a = null;
        $effect_b = null;

        if (!empty($base)) {
            $effect_a = $base['effect'] === 'allow';
        }

        if (!empty($incoming)) {
            $effect_b = $incoming['effect'] === 'allow';
        }

        if ($preference === 'allow') { // Merging preference is to allow
            // If at least one set has allowed rule, then allow the URL
            if (in_array($effect_a, [ true, null ], true)
                || in_array($effect_b, [ true, null ], true)
            ) {
                $result = [ 'effect' => 'allow' ];
            } elseif (!is_null($effect_a)) { // Is base rule set has URL defined?
                $result = $base;
            } else {
                $result = $incoming;
            }
        } else { // Merging preference is to deny access by default
            if ($effect_a === false) {
                $result = $base;
            } elseif ($effect_b === false) {
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
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_current_area()
    {
        if (is_admin()) {
            $result = 'backend';
        } elseif (wp_is_json_request()) {
            $result = 'api';
        } else {
            $result = 'frontend';
        }

        return $result;
    }

}