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
     * List of allowed rule types
     *
     * @version 7.0.0
     */
    const ALLOWED_RULE_TYPES = [
        'allow',
        'deny',
        'custom_message',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

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
     * Validate and prepare rule data
     *
     * This method takes flat URL rule data and convert it into format that is stored
     * in the database.
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    public function convert_to_rule(array $data)
    {
        // First, let's validate tha the rule type is correct
        if (!in_array($data['type'], self::ALLOWED_RULE_TYPES, true)) {
            throw new InvalidArgumentException('The valid `type` is required');
        }

        // Now, validating that the URL is acceptable
        // Parse and validate the incoming URL
        if ($data['url'] === '*') {
            $url = '*';
        } else {
            $parsed = wp_parse_url($data['url']);
            $url    = wp_validate_redirect(
                empty($parsed['path']) ? '/' : $parsed['path']
            );
        }

        // Adding query params if provided
        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        if (empty($url)) {
            throw new InvalidArgumentException('The valid `url` is required');
        }

        $result = [
            'effect' => $data['type'] === 'allow' ? 'allow' : 'deny',
            'url'    => $url
        ];

        // Redirect data will be stored in the "redirect" property
        if (!in_array($data['type'], ['allow', 'deny'], true)) {
            $result['redirect'] = [
                'type' => $data['type']
            ];
        }

        if ($data['type'] === 'custom_message') {
            $message = wp_kses_post($data['message']);

            if (empty($message)) {
                throw new InvalidArgumentException('The `message` is required');
            } else {
                $result['redirect']['message'] = $message;
            }
        } elseif ($data['type'] === 'page_redirect') {
            $page_id = intval($data['redirect_page_id']);

            if ($page_id === 0) {
                throw new InvalidArgumentException(
                    'The `redirect_page_id` is required'
                );
            } else {
                $result['redirect']['redirect_page_id'] = $page_id;
            }
        } elseif ($data['type'] === 'url_redirect') {
            $redirect_url = wp_validate_redirect($data['redirect_url']);

            if (empty($redirect_url)) {
                throw new InvalidArgumentException(
                    'The valid `redirect_url` is required'
                );
            } else {
                $result['redirect']['redirect_url'] = $redirect_url;
            }
        } elseif ($data['type'] === 'trigger_callback') {
            if (!is_callable($data['callback'], true)) {
                throw new InvalidArgumentException(
                    'The valid `callback` is required'
                );
            } else {
                $result['redirect']['callback'] = $data['callback'];
            }
        }

        if (!empty($data['http_status_code'])) {
            $code = intval($data['http_status_code']);

            if ($code >= 300) {
                $result['redirect']['http_status_code'] = $code;
            }
        }

        return apply_filters('aam_validate_url_access_rule_filter', $result, $data);
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