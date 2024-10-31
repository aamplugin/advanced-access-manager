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
     * Determine the max user level based on provided array of capabilities
     *
     * @param array $caps
     *
     * @return int
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function get_max_user_level($caps)
    {
        $max = 0;

        if (is_array($caps)) {
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

            // Finally sanitize the safe URL
            $result = wp_validate_redirect($result);
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

}