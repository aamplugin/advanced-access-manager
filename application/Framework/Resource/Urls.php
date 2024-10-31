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
class AAM_Framework_Resource_Urls
implements
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::URLS;

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
        $config = AAM::api()->configs();

        // If preference is not explicitly defined, fetch it from the AAM configs
        $preference = $config->get_config(
            'core.settings.merge.preference'
        );

        $preference = $config->get_config(
            'core.settings.' . constant('static::TYPE') . '.merge.preference',
            $preference
        );

        $base = $this->_permissions;

        // First get the complete list of unique keys
        $rule_keys = array_unique([
            ...array_keys($incoming),
            ...array_keys($base)
        ]);

        foreach($rule_keys as $rule_key) {
            $result[$rule_key] = $this->_merge_permissions(
                isset($base[$rule_key]) ? $base[$rule_key] : null,
                isset($incoming[$rule_key]) ? $incoming[$rule_key] : null,
                $preference
            );
        }

        return $result;
    }

    /**
     * Find permission that matches given URL
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permission_by_url($url)
    {
        // Step #1. Let's check if there is a full URL (potentially with query
        //          params explicitly defined
        $result = $this->_find_permission_by_url($url);

        // Step #2. Parsing the incoming URL and checking if there is the
        //          same URL without query params defined
        if (is_null($result)) {
            $parsed_url = AAM_Framework_Utility_Misc::parse_url($url);

            if (!empty($parsed_url['path'])) {
                $result = $this->_find_permission_by_url($parsed_url['path']);
            }
        }

        return apply_filters(
            'aam_get_permission_by_url_filter',
            $result,
            $url,
            $this->_sort_permissions(),
            $this
        );
    }

    /**
     * Check whether URL is restricted or not
     *
     * @param string $url
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($url)
    {
        $permission = $this->get_permission_by_url($url);

        if (!empty($permission)) {
            $result = $permission['effect'] !== 'allow';
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get redirect defined for a given URL
     *
     * This method will return redirect type if URL permission exists. Otherwise,
     * `null` is returned.
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($url)
    {
        $match  = $this->get_permission_by_url($url);
        $result = null;

        if (!empty($match)) {
            if (array_key_exists('redirect', $match)) {
                $result = $match['redirect'];
            } else {
                $result = [ 'type' => 'default' ];
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
     * @access private
     * @version 7.0.0
     */
    private function _find_permission_by_url($search_url)
    {
        $result = null;
        $target = AAM_Framework_Utility_Misc::parse_url($search_url);

        foreach ($this->_sort_permissions() as $url => $permission) {
            $current = AAM_Framework_Utility_Misc::parse_url($url);

            // Check if two relative paths match
            $matched = $target['path'] === $current['path'];

            // If yes, we also verify that the query params overlap, if provided
            if ($matched && !empty($current['params'])) {
                foreach($current['params'] as $key => $val) {
                    $matched = $matched && array_key_exists($key, $target['params'])
                                    && ($target['params'][$key] === $val);
                }
            }

            if ($matched) {
                $result = $permission;
            }
        }

        return $result;
    }

    /**
     * Sort all permissions before processing
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _sort_permissions()
    {
        // Property organize all the settings
        // Place all "allowed" rules in the end of the list to allow the ability to
        // define whitelisted set of conditions
        $denied = $allowed = [];

        foreach ($this->_permissions as $url => $permission) {
            if ($permission['effect'] === 'allow') {
                $allowed[$url] = $permission;
            } else {
                $denied[$url] = $permission;
            }
        }

        return array_merge($denied, $allowed);
    }

}