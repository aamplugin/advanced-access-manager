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
class AAM_Framework_Service_Urls
implements
    AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * List of allowed rule types
     *
     * @version 7.0.0
     */
    const ALLOWED_RULE_TYPES = [
        'default',
        'custom_message',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

    /**
     * Return list of all defined URL permissions
     *
     * @param array $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_urls($inline_context = null)
    {
        try {
            $result      = [];
            $resource    = $this->_get_resource(true, $inline_context);
            $permissions = $resource->get_permissions();

            foreach($permissions as $url => $permission) {
                array_push($result, $this->_prepare_url_model(
                    $url, $permission, $resource
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Alias for the get_urls method
     *
     * @param array $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function urls($inline_context = null)
    {
        return $this->get_urls($inline_context);
    }

    /**
     * Get permission for a given URL
     *
     * @param string $url
     * @param array  $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_url($url, $inline_context = null)
    {
        try {
            $resource    = $this->_get_resource(true, $inline_context);
            $permissions = $resource->get_permissions();
            $url         = AAM_Framework_Utility_Misc::sanitize_url($url);

            if (!array_key_exists($url, $permissions)) {
                throw new OutOfRangeException(sprintf(
                    'Permission for URL "%s" does not exist', $url
                ));
            }

            $result = $this->_prepare_url_model(
                $url, $permissions[$url], $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Alias for the get_url method
     *
     * @param string $url
     * @param array  $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function url($url, $inline_context = null)
    {
        return $this->get_url($url, $inline_context);
    }

    /**
     * Allow access to a given URL
     *
     * @param string|array $url
     * @param mixed        $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($url, $inline_context = null)
    {
        $result = true;

        try {
            foreach((array)$url as $u) {
                $result = $result && $this->_set_url_permission(
                    $u, 'allow', null, $inline_context
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Restrict access to a given URL
     *
     * @param string|array $url
     * @param array        $redirect       [optional]
     * @param mixed        $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($url, $redirect = null, $inline_context = null)
    {
        $result = true;

        try {
            foreach((array)$url as $u) {
                $result = $result && $this->_set_url_permission(
                    $u, 'deny', $redirect, $inline_context
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Alias method for the redirect
     *
     * @param string|array $url
     * @param array        $redirect       [optional]
     * @param mixed        $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function deny($url, $redirect = null, $inline_context = null)
    {
        return $this->restrict($url, $redirect, $inline_context);
    }

    /**
     * Reset all rules or any given
     *
     * @param string|array $url            [optional]
     * @param mixed        $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($url = null, $inline_context = null)
    {
        $result = true;

        try {
            $resource = $this->_get_resource(false, $inline_context);

            if (!empty($url)) {
                foreach((array)$url as $u) {
                    $result = $result && $this->_delete_url_permission(
                        $u, $inline_context
                    );
                }
            } else {
                $result = $resource->reset();
            }

            if (!$result) {
                throw new RuntimeException('Failed to reset permissions');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Determine if given URL is restricted
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($url, $inline_context = null)
    {
        try {
            $resource = $this->_get_resource(false, $inline_context);
            $url      = AAM_Framework_Utility_Misc::sanitize_url($url);

            // Step #1. Let's check if there is URL with query params explicitly
            //          defined
            $result = $resource->is_restricted($url);

            if (is_null($result)) {
                // Step #2. Parsing the incoming URL and checking if there is the
                //          same URL without query params defined
                $parsed_url = wp_parse_url($url);

                if (!empty($parsed_url['path'])) {
                    $result = $resource->is_restricted($parsed_url['path']);
                }
            }

            $result = apply_filters(
                'aam_url_is_restricted_filter',
                $result,
                $url,
                $this
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Alias method for the is_restricted
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied($url, $inline_context = null)
    {
        return $this->is_restricted($url, $inline_context);
    }

    /**
     * Determine if given URL is allowed
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($url, $inline_context = null)
    {
        $result = $this->is_restricted($url, $inline_context);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get redirect for given URL
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect($url, $inline_context = null)
    {
        try {
            $resource = $this->_get_resource(false, $inline_context);
            $url      = AAM_Framework_Utility_Misc::sanitize_url($url);

            // Let's try to get permissions for the URL as is
            $permission = $resource->get_permission($url);

            if (is_null($permission)) {
                // Otherwise, parse the URL by removing query params and try to get
                // permissions just by URL
                $parsed_url = wp_parse_url($url);

                if (!empty($parsed_url['path'])) {
                    $permission = $resource->get_permission($parsed_url['path']);
                }
            }

            if (!empty($permission['redirect'])) {
                $result = $permission['redirect'];
            } else {
                $result = null;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get redirect for given URL
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function redirect($url, $inline_context = null)
    {
        return $this->get_redirect($url, $inline_context);
    }

    /**
     * Set URL permission
     *
     * @param string     $url
     * @param string     $effect
     * @param array|null $redirect
     * @param mixed      $inline_context Runtime context
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _set_url_permission(
        $url, $effect, $redirect = null, $inline_context = null
    ) {
        $resource = $this->_get_resource(false, $inline_context);

        // Prepare the permission model
        $permission = [ 'effect' => $effect ];

        if ($effect !== 'allow' && !empty($redirect)) {
            $permission['redirect'] = $redirect;
        }

        // Sanitize the incoming URL
        $url = AAM_Framework_Utility_Misc::sanitize_url($url);

        if (!$resource->set_permission($url, $permission)) {
            throw new RuntimeException('Failed to persist settings');
        }

        return true;
    }

    /**
     * Delete URL permission
     *
     * @param string $url
     * @param mixed  $inline_context
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _delete_url_permission($url, $inline_context)
    {
        $resource    = $this->_get_resource(false, $inline_context);
        $permissions = $resource->get_permissions(true);
        $url         = AAM_Framework_Utility_Misc::sanitize_url($url);

        // Note! User can delete only explicitly set rule (overwritten rule)
        if (array_key_exists($url, $permissions)) {
            unset($permissions[$url]);

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist changes');
            }
        }

        return true;
    }

    /**
     * Get URL resource
     *
     * @param boolean $reload
     * @param array   $inline_context
     *
     * @return AAM_Framework_Resource_Urls
     *
     * @access public
     * @version 7.0.0
     */
    private function _get_resource($reload = false, $inline_context = null)
    {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Resource_Urls::TYPE, null, $reload
        );
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param string                      $url
     * @param array                       $permission
     * @param AAM_Framework_Resource_Urls $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_url_model($url, $permission, $resource)
    {
        // Determine if current permission is overwritten
        $explicit     = $resource->get_permissions(true);
        $is_inherited = !array_key_exists($url, $explicit);

        return array_merge($permission, [
            'url'          => $url,
            'is_inherited' => $is_inherited
        ]);
    }

}