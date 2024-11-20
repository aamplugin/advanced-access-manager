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
     * List of allowed redirect types
     *
     * @version 7.0.0
     */
    const ALLOWED_REDIRECT_TYPES = [
        'default',
        'custom_message',
        'page_redirect',
        'url_redirect',
        'trigger_callback',
        'login_redirect'
    ];

    /**
     * Get the array of defined permissions
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions()
    {
        try {
            $result      = [];
            $resource    = $this->_get_resource();
            $permissions = $resource->get_permissions();

            foreach($permissions as $url => $permission) {
                array_push($result, $this->_prepare_url_schema_model(
                    $url, $permission, $resource
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
     *
     * @access public
     * @version 7.0.0
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
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permission($url_schema)
    {
        try {
            $resource    = $this->_get_resource();
            $permissions = $resource->get_permissions();
            $url_schema  = AAM_Framework_Utility_Misc::sanitize_url($url_schema);

            if (!array_key_exists($url_schema, $permissions)) {
                throw new OutOfRangeException(sprintf(
                    'Permission for URL schema "%s" does not exist', $url_schema
                ));
            }

            $result = $this->_prepare_url_schema_model(
                $url_schema, $permissions[$url_schema], $resource
            );
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
     *
     * @access public
     * @version 7.0.0
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
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($url_schema)
    {
        $result = true;

        try {
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
     * @param array        $redirect       [optional]
     *
     * @return bool|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($url_schema, $redirect = null)
    {
        $result = true;

        try {
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
     * Alias method for the redirect
     *
     * @param string|array $url_schema
     * @param array        $redirect       [optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function deny($url_schema, $redirect = null)
    {
        return $this->restrict($url_schema, $redirect);
    }

    /**
     * Reset all rules or any given
     *
     * @param string|array|null $url_schema [optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($url_schema = null)
    {
        $result = true;

        try {
            if (!empty($url_schema)) {
                foreach((array) $url_schema as $schema) {
                    $result = $result && $this->_delete_permission($schema);
                }
            } else {
                $result = $this->_get_resource()->reset();
            }

            if (!$result) {
                throw new RuntimeException('Failed to reset permissions');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get URL resource
     *
     * @param string $url
     *
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    public function get_url($url)
    {
        try {
            $result = $this->_get_resource(
                AAM_Framework_Utility_Misc::sanitize_url($url)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_url method
     *
     * @param string $url
     *
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    public function url($url)
    {
        return $this->get_url($url);
    }

    /**
     * Determine if given URL is restricted
     *
     * @param string $url
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($url)
    {
        try {
            $resource = $this->_get_resource(
                AAM_Framework_Utility_Misc::sanitize_url($url),
            );

            $result = apply_filters(
                'aam_url_is_restricted_filter',
                $resource->is_restricted(),
                $url,
                $this
            );

            // Finally making sure we are returning correct value
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias method for the is_restricted
     *
     * @param string $url
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied($url)
    {
        return $this->is_restricted($url);
    }

    /**
     * Determine if given URL is allowed
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($url)
    {
        $result = $this->is_restricted($url);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get redirect for given URL
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
        try {
            $result = $this->_get_resource(
                AAM_Framework_Utility_Misc::sanitize_url($url)
            )->get_redirect();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get redirect for given URL
     *
     * @param string $url
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function redirect($url)
    {
        return $this->get_redirect($url);
    }

    /**
     * Set URL schema permission
     *
     * @param string     $url_schema
     * @param string     $effect
     * @param array|null $redirect
     *
     * @return bool
     *
     * @access private
     * @version 7.0.0
     */
    private function _set_permission($url_schema, $effect, $redirect = null)
    {
        $resource = $this->_get_resource();

        // Prepare the permission model
        $permission = [ 'effect' => $effect ];

        if ($effect !== 'allow' && !empty($redirect)) {
            $permission['redirect'] = $redirect;
        }

        // Sanitize the incoming URL
        $url_schema = AAM_Framework_Utility_Misc::sanitize_url($url_schema);

        // Prepare array of new permissions
        $perms = array_merge($resource->get_permissions(true), [
            $url_schema => $permission
        ]);

        if (!$resource->set_permissions($perms)) {
            throw new RuntimeException('Failed to persist settings');
        }

        return true;
    }

    /**
     * Delete URL permission
     *
     * @param string $url_schema
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _delete_permission($url_schema)
    {
        $resource    = $this->_get_resource();
        $permissions = $resource->get_permissions(true);
        $url_schema  = AAM_Framework_Utility_Misc::sanitize_url($url_schema);

        // Note! User can delete only explicitly set permissions (customized)
        if (array_key_exists($url_schema, $permissions)) {
            unset($permissions[$url_schema]);

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist changes');
            }
        }

        return true;
    }

    /**
     * Get URL resource
     *
     * @param string|null $url
     *
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    private function _get_resource($url = null)
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::URL, $url
        );
    }

    /**
     * Normalize and prepare the rule model
     *
     * @param string                     $url_schema
     * @param array                      $permission
     * @param AAM_Framework_Resource_Url $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_url_schema_model($url_schema, $permission, $resource)
    {
        // Determine if current permission is overwritten
        $explicit     = $resource->get_permissions(true);
        $is_inherited = !array_key_exists($url_schema, $explicit);

        return array_merge($permission, [
            'url_schema'   => $url_schema,
            'is_inherited' => $is_inherited
        ]);
    }

    /**
     * Sanitize incoming URL
     *
     * @param string $url_schema
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _sanitize_url_schema($url_schema)
    {
        $result = apply_filters(
            'aam_url_sanitize_url_filter',
            AAM_Framework_Utility_Misc::sanitize_url($url_schema),
            $url_schema
        );

        if ($result === false) {
            throw new InvalidArgumentException('The incoming URL schema is invalid');
        }

        return $result;
    }

}