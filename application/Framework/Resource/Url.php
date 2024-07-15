<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URL resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Url implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * Resource type
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::URL;

    /**
     * Merge URL access settings
     *
     * @param array $target
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_settings($incoming_settings)
    {
        $merged = [];

        // Converting the two sets into true/false representation where false is
        // when effect === "allow" and everything else is true
        $set1 = array_map(
            function($v) { return $v['effect'] !== 'allow'; }, $incoming_settings
        );

        $set2 = array_map(
            function($v) { return $v['effect'] !== 'allow'; }, $this->_settings
        );

        $result = $this->_merge_binary_settings(
            $set1, $set2
        );

        // Convert back to the actual properties
        foreach($result as $url => $effect) {
            if ($effect === false) { // Which means we are allowing
                $merged[$url] = [ 'effect' => 'allow' ];
            } elseif (isset($this->_settings[$url])) {
                $merged[$url] = $this->_settings[$url];
            } else {
                $merged[$url] = $incoming_settings[$url];
            }
        }

        return $merged;
    }

    /**
     * Check whether URL is restricted or not
     *
     * @param string $url
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($url = null)
    {
        $rule = $this->_find_matching_rule($url);

        return !empty($rule) && ($rule['effect'] !== 'allow');
    }

    /**
     * Get redirect if URL is restricted
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($url = null)
    {
        $rule = $this->_find_matching_rule($url);

        return $rule && ($rule['effect'] !== 'allow') ? $rule['redirect'] : null;
    }

    /**
     * Get URL access rule that matches given URL
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    private function _find_matching_rule($url = null)
    {
        $result = null;
        $target = $this->_parse_url(empty($url) ? $this->_internal_id : $url);

        foreach ($this->_sort_rules() as $url => $rule) {
            $meta = wp_parse_url($url);
            $out  = [];

            if (!empty($meta['query'])) {
                parse_str($meta['query'], $out);
            }

            // Normalize the target URI
            $path = rtrim(isset($meta['path']) ? $meta['path'] : '', '/');

            // Check if two URLs are equal
            $uri_matched = ($target['url'] === $path);

            if ($uri_matched === false) {
                $uri_matched = apply_filters(
                    'aam_uri_match_filter', false, $url, $target['url']
                );
            }

            // Perform the initial match for the query params if defined
            $same          = array_intersect_assoc($target['query_params'], $out);
            $query_matched = empty($out) || (count($same) === count($out));

            if ($uri_matched && $query_matched) {
                $result = $rule;
            }
        }

        return apply_filters('aam_uri_match_result_filter', $result);
    }

    /**
     * Parse URL so it can be used for internal evaluations
     *
     * @param string $url
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _parse_url($url)
    {
        $normalized = call_user_func(
            function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower',
            rtrim($url,  '/')
        );

        // Parse URL for further processing
        $parsed       = wp_parse_url($normalized);
        $query_params = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
        }

        return [
            'url'          => $normalized,
            'query_params' => $query_params
        ];
    }

    /**
     * Sort rules before processing
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _sort_rules()
    {
        // Property organize all the settings
        // Place all "allowed" rules in the end of the list to allow the ability to
        // define whitelisted set of conditions
        $denied = $allowed = [];

        foreach ($this->_settings as $url => $rule) {
            if ($rule['effect'] === 'allow') {
                $allowed[$url] = $rule;
            } else {
                $denied[$url] = $rule;
            }
        }

        return array_merge($denied, $allowed);
    }

}