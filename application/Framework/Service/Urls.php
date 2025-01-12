<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM URLs service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Urls implements AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get the array of defined permissions
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Consider moving to AAM_Service_Url
     */
    public function get_permissions()
    {
        try {
            $result   = [];
            $resource = $this->_get_resource();

            foreach($resource->get_permissions() as $url => $permissions) {
                array_push($result, $this->_prepare_url_schema_model(
                    $url, $permissions['access']
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_permissions method
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Consider moving to AAM_Service_Url
     */
    public function permissions()
    {
        return $this->get_permissions();
    }

    /**
     * Get permission for a given URL schema
     *
     * @param string $url_schema
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Consider moving to AAM_Service_Url
     */
    public function get_permission($url_schema)
    {
        try {
            $resource    = $this->_get_resource();
            $permission = $resource->get_permission(
                $this->_normalize_resource_identifier($url_schema),
                'access'
            );

            if (empty($permission)) {
                throw new OutOfRangeException(sprintf(
                    'Permission for URL schema "%s" does not exist', $url_schema
                ));
            }

            $result = $this->_prepare_url_schema_model($url_schema, $permission);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_url method
     *
     * @param string $url_schema
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Consider moving to AAM_Service_Url
     */
    public function permission($url_schema)
    {
        return $this->get_permission($url_schema);
    }

    /**
     * Allow access to a given URL
     *
     * @param string|array $url_schema
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($url_schema)
    {
        try {
            $result = true;

            foreach((array) $url_schema as $schema) {
                $result = $result && $this->_set_permission($schema, 'allow');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Restrict access to a given URL
     *
     * @param string|array $url_schema
     * @param array        $redirect   [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($url_schema, $redirect = null)
    {
        try {
            $result = true;

            foreach((array) $url_schema as $schema) {
                $result = $result && $this->_set_permission(
                    $schema, 'deny', $redirect
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset all rules or any given
     *
     * @param string|array|null $url_schema [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($url_schema = null)
    {
        try {
            if (!empty($url_schema)) {
                $result = $this->_get_resource()->reset(
                    $this->_normalize_resource_identifier($url_schema)
                );
            } else {
                $result = $this->_get_resource()->reset();
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if given URL is denied
     *
     * @param string $url
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($url)
    {
        try {
            $permission = $this->_get_permission_by_url($url);

            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            } else {
                $result = null;
            }

            $result = apply_filters(
                'aam_url_is_denied_filter',
                $result,
                $url,
                $permission
            );

            // Finally making sure we are returning correct value
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if given URL is allowed
     *
     * @param string $url
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($url)
    {
        $result = $this->is_denied($url);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get redirect for given URL
     *
     * @param string $url
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_redirect($url)
    {
        $result = null;

        try {
            $permission = $this->_get_permission_by_url($url);

            if (!empty($permission)) {
                if (array_key_exists('redirect', $permission)) {
                    $result = $permission['redirect'];
                } else {
                    $result = [ 'type' => 'default' ];
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Find permission that matches given URL
     *
     * @param string $url
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_permission_by_url($url)
    {
        $identifier  = $this->_normalize_resource_identifier($url);
        $permissions = $this->_sort_permissions(
            $this->_get_resource()->get_permissions()
        );

        // Step #1. Let's check if there is a full URL (potentially with query
        //          params explicitly defined
        $result = $this->_find_permission_by_url($identifier, $permissions);

        // Step #2. Parsing the incoming URL and checking if there is the
        //          same URL without query params defined
        if (is_null($result)) {
            $parsed_url = AAM_Framework_Manager::_()->misc->parse_url($identifier);

            if (!empty($parsed_url['path'])) {
                $result = $this->_find_permission_by_url(
                    $parsed_url['path'], $permissions
                );
            }
        }

        return apply_filters(
            'aam_get_permission_by_url_filter',
            $result,
            $url,
            $permissions
        );
    }

    /**
     * Get URL access rule that matches given URL
     *
     * @param string $url
     * @param array  $permissions
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _find_permission_by_url($search_url, $permissions)
    {
        $result = null;
        $target = AAM_Framework_Manager::_()->misc->parse_url($search_url);

        foreach ($permissions as $url => $permission) {
            $current = AAM_Framework_Manager::_()->misc->parse_url($url);

            // Check if two relative paths match
            $matched = $target['path'] === $current['path'];

            // If yes, we also verify that the query params overlap, if provided
            if ($matched && !empty($current['params'])) {
                foreach($current['params'] as $key => $val) {
                    $matched = $matched
                        && array_key_exists($key, $target['params'])
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
     * @param array $permissions
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _sort_permissions($permissions)
    {
        // Property organize all the settings
        // Place all "allowed" rules in the end of the list to allow the ability to
        // define whitelisted set of conditions
        $denied = $allowed = [];

        foreach ($permissions as $url => $permission) {
            if ($permission['access']['effect'] !== 'allow') {
                $denied[$url] = $permission['access'];
            } else {
                $allowed[$url] = $permission['access'];
            }
        }

        return array_merge($denied, $allowed);
    }

    /**
     * Set URL schema permission
     *
     * @param string     $url_schema
     * @param string     $effect
     * @param array|null $redirect
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _set_permission($url_schema, $effect, $redirect = null)
    {
        // Prepare the permission model
        $permission = [ 'effect' => $effect ];

        if ($effect !== 'allow' && !empty($redirect)) {
            $permission['redirect'] = $redirect;
        }

        return $this->_get_resource()->set_permission(
            $this->_normalize_resource_identifier($url_schema),
            'access',
            $permission
        );
    }

    /**
     * Get URL resource
     *
     * @return AAM_Framework_Resource_Url
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::URL
        );
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param string $url_schema
     * @param array  $permission
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_url_schema_model($url_schema, $permission)
    {
        $result = [
            'url_schema' => $url_schema
        ];

        // Remove system attributes
        foreach($permission as $key => $value) {
            if ($key === '__inherited') {
                $result['is_inherited'] = $value;
            } elseif (strpos($key, '__') === false) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    private function _normalize_resource_identifier($url_schema)
    {
        $result = $this->misc->sanitize_url($url_schema);

        if (empty($result)) {
            throw new InvalidArgumentException('Invalid URL resource identifier');
        }

        return $result;
    }

}