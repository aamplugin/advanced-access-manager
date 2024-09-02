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
class AAM_Framework_Resource_Url
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::URL;

    /**
     * Merge URL access settings
     *
     * @param array $incoming
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_permissions($incoming)
    {
        $result = [];
        $config = AAM_Framework_Manager::configs();
        $base   = $this->_permissions;

        // If preference is not explicitly defined, fetch it from the AAM configs
        $preference = $config->get_config(
            'core.settings.merge.preference'
        );

        $preference = $config->get_config(
            'core.settings.' . constant('static::TYPE') . '.merge.preference',
            $preference
        );

        // First get the complete list of unique keys
        $rule_keys = array_unique([
            ...array_keys($incoming),
            ...array_keys($base)
        ]);

        foreach($rule_keys as $rule_key) {
            $effect_a = null;
            $effect_b = null;

            if (isset($base[$rule_key])) {
                $effect_a = $base[$rule_key]['effect'] === 'allow';
            }

            if (isset($incoming[$rule_key])) {
                $effect_b = $incoming[$rule_key]['effect'] === 'allow';
            }

            if ($preference === 'allow') { // Merging preference is to allow
                // If at least one set has allowed rule, then allow the URL
                if (in_array($effect_a, [ true, null ], true)
                    || in_array($effect_b, [ true, null ], true)
                ) {
                    $result[$rule_key] = [ 'effect' => 'allow' ];
                } elseif (!is_null($effect_a)) { // Is base rule set has URL defined?
                    $result[$rule_key] = $base[$rule_key];
                } else {
                    $result[$rule_key] = $incoming[$rule_key];
                }
            } else { // Merging preference is to deny access by default
                if ($effect_a === false) {
                    $result[$rule_key] = $base[$rule_key];
                } elseif ($effect_b === false) {
                    $result[$rule_key] = $incoming[$rule_key];
                } else {
                    $result[$rule_key] = [ 'effect' => 'allow' ];
                }
            }
        }

        return $result;
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
        $result = null;
        $rule   = $this->_find_matching_rule($url);

        if (!empty($rule) && $rule['effect'] === 'deny') {
            if (isset($rule['redirect'])) {
                $result = $rule['redirect'];
            } else {
                $result = [ 'type' => 'default' ]; // Just do the default redirect
            }
        }

        return $result;
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
                    'aam_matching_url_filter', false, $url, $target['url']
                );
            }

            // Perform the initial match for the query params if defined
            $same          = array_intersect_assoc($target['query_params'], $out);
            $query_matched = empty($out) || (count($same) === count($out));

            if ($uri_matched && $query_matched) {
                $result = $rule;
            }
        }

        return $result;
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

        foreach ($this->_permissions as $url => $rule) {
            if ($rule['effect'] === 'allow') {
                $allowed[$url] = $rule;
            } else {
                $denied[$url] = $rule;
            }
        }

        return array_merge($denied, $allowed);
    }

}