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
     * @param array $inline_context Context
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
     * @param array $inline_context Context
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
     * @param array  $inline_context Runtime context
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
     * @param array  $inline_context Runtime context
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
     * @param string $url
     * @param mixed  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($url, $inline_context = null)
    {
        try {
            $result = $this->_set_url_permission(
                $url, 'allow', null, $inline_context
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Restrict access to a given URL
     *
     * @param string $url
     * @param array  $redirect
     * @param mixed  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($url, $redirect = null, $inline_context = null)
    {
        try {
            $result = $this->_set_url_permission(
                $url, 'deny', $redirect, $inline_context
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all rules
     *
     * @param string $url
     * @param mixed  $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($url = null, $inline_context = null)
    {
        try {
            $resource = $this->_get_resource(false, $inline_context);

            if (!empty($url) && is_string($url)) {
                $result = $this->_delete_url_permission($url, $inline_context);
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
            $result   = apply_filters(
                'aam_url_is_restricted_filter',
                $resource->is_restricted($url),
                $url,
                $this
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
            $perm     = $resource->get_permission($url);
            $result   = !empty($perm['redirect']) ? $perm['redirect'] : null;
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    private function _get_resource($reload = false, $inline_context = null)
    {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Resource_Url::TYPE, null, $reload
        );
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param string                     $url
     * @param array                      $permission
     * @param AAM_Framework_Resource_Url $resource
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