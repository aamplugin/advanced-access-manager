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
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::URL;

    /**
     * Check whether URL is restricted or not
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted()
    {
        if (empty($this->_internal_id)) {
            throw new InvalidArgumentException(
                'The URL resource has to be initialized with valid URL first'
            );
        }

        $permission = $this->_get_permission_by_url($this->_internal_id);

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
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect()
    {
        if (empty($this->_internal_id)) {
            throw new InvalidArgumentException(
                'The URL resource has to be initialized with valid URL first'
            );
        }

        $match  = $this->_get_permission_by_url($this->_internal_id);
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
     * Find permission that matches given URL
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_permission_by_url($url)
    {
        // Step #1. Let's check if there is a full URL (potentially with query
        //          params explicitly defined
        $result = $this->_find_permission_by_url($url);

        // Step #2. Parsing the incoming URL and checking if there is the
        //          same URL without query params defined
        if (is_null($result)) {
            $parsed_url = AAM_Framework_Manager::_()->misc->parse_url($url);

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
        $target = AAM_Framework_Manager::_()->misc->parse_url($search_url);

        foreach ($this->_sort_permissions() as $url => $permission) {
            $current = AAM_Framework_Manager::_()->misc->parse_url($url);

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

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        $manager = AAM_Framework_Manager::_();

        // Fetch list of statements for the resource Url
        $list = $manager->policies($this->get_access_level())->statements('Url:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';
            $parsed = explode(':', $stm['Resource'], 2);

            if (!empty($parsed[1])) {
                $url         = $manager->misc->sanitize_url($parsed[1]);
                $permissions = array_merge([
                    $url => [
                        'effect'   => $effect !== 'allow' ? 'deny' : 'allow',
                        'redirect' => $manager->policy->convert_statement_redirect(
                            $stm
                        )
                    ]
                ], $permissions);
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}