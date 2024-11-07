<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Resource class
 *
 * @method WP_Post get_core_instance()
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post
implements
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    const PERMISSION_LIST = 'list';
    const PERMISSION_HIDDEN = 'list';
    const PERMISSION_HIDE = 'list';

    /**
     * Add a single permission
     *
     * @param string            $permission_key
     * @param array|string|null $permission
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function add_permission($permission_key, $permission = null)
    {
        $permissions = $this->_explicit_permissions;
        $permissions[$permission_key] = $this->_sanitize_permission(
            is_null($permission) ? 'deny' : $permission,
            $permission_key
        );

        return $this->set_permissions($permissions, true);
    }

    /**
     * Add multiple permissions
     *
     * @param array $permissions
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function add_permissions($permissions)
    {
        $permissions = $this->_explicit_permissions;

        foreach($permissions as $key => $value) {
            $permissions[$key] = $this->_sanitize_permission($value, $key);
        }

        return $this->set_permissions($permissions, true);
    }

    /**
     * Determine if post is hidden on given area
     *
     * @param string $area Can be either frontend, backend or api
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden_on($area)
    {
        $result     = null;
        $permission = null;

        if (!empty($this->_permissions['list'])) {
            $permission = $this->_permissions['list'];
        }

        if (!is_null($permission)) {
            if ($permission['effect'] === 'deny') {
                $result = in_array($area, $permission['on'], true);
            } else {
                $result = false;
            }
        }

        return apply_filters('aam_post_is_hidden_on_filter', $result, $this);
    }

    /**
     * Determine if post is hidden on currently viewed area
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden()
    {
        if (is_admin()) {
            $area = 'backend';
        } elseif (defined('REST_REQUEST') && REST_REQUEST) {
            $area = 'api';
        } else {
            $area = 'frontend';
        }

        return $this->is_hidden_on($area);
    }

    /**
     * Determine if current post is password protected
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_password_protected()
    {
        $result     = null;
        $permission = null;

        // Evaluate if we even have the read permission
        if (!empty($this->_permissions['read'])) {
            $permission = $this->_permissions['read'];
        }

        if (!is_null($permission)) {
            if (!empty($permission['restriction_type'])) {
                $restriction_type = $permission['restriction_type'];
            } else {
                $restriction_type = null;
            }

            if ($restriction_type === 'password_protected') {
                $result = $permission['effect'] === 'deny'
                    && !empty($permission['password']);
            }
        }

        return apply_filters(
            'aam_post_is_password_protected_filter', $result, $this
        );
    }

    /**
     * Façade function that determines if post is restricted for direct access
     *
     * This method verifies is post is set as restricted by checking following:
     * - Post is set as restricted without any additional conditions;
     * - Post access is expired
     * - The aam_post_is_restricted_filter returns positive result
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted()
    {
        $result     = null;
        $permission = null;

        // Evaluate if we even have the read permission
        if (!empty($this->_permissions['read'])) {
            $permission = $this->_permissions['read'];
        }

        if (!is_null($permission)) {
            $restriction_type = null;

            if (!empty($permission['restriction_type'])) {
                $restriction_type = $permission['restriction_type'];
            } else {
                $restriction_type = 'default';
            }

            if ($permission['effect'] === 'deny') {
                if ($restriction_type === 'default') {
                    $result = true;
                } elseif ($restriction_type === 'expire') {
                    $result = time() >= intval($permission['expires_after']);
                }
            } else {
                $result = false;
            }
        }

        return apply_filters('aam_post_is_restricted_filter', $result, $this);
    }

    /**
     * Façade function that determines if access level has certain permission
     *
     * @param string $permission
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to($permission)
    {
        if ($permission === 'read') {
            $decision = !$this->is_restricted();
        } elseif (array_key_exists($permission, $this->_permissions)) {
            $decision = $this->_permissions[$permission]['effect'] === 'allow';
        } else {
            $decision = null;
        }

        return apply_filters(
            'aam_post_is_allowed_to_filter',
            is_bool($decision) ? $decision : null,
            $permission,
            $this
        );
    }

    /**
     * Façade function that determines if access level does not have certain
     * permission
     *
     * @param string $permission
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied_to($permission)
    {
        if ($permission === 'read') {
            $decision = $this->is_restricted();
        } elseif (array_key_exists($permission, $this->_permissions)) {
            $decision = $this->_permissions[$permission]['effect'] !== 'allow';
        } else {
            $decision = null;
        }

        return apply_filters(
            'aam_post_is_denied_to_filter',
            is_bool($decision) ? $decision : null,
            $permission,
            $this
        );
    }

    /**
     * Determine if current post has teaser message
     *
     * Instead of a post's content, the specified teaser message is displayed
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function has_teaser_message()
    {
        $result     = null;
        $permission = null;

        // Evaluate if we even have the read permission
        if (!empty($this->_permissions['read'])) {
            $permission = $this->_permissions['read'];
        }

        if (!is_null($permission)) {
            if (!empty($permission['restriction_type'])) {
                $restriction_type = $permission['restriction_type'];
            } else {
                $restriction_type = null;
            }

            if ($restriction_type === 'teaser_message') {
                $result = $permission['effect'] === 'deny';
            }
        }

        return apply_filters('aam_post_has_teaser_message_filter', $result, $this);
    }

    /**
     * Determine if current post has redirect defined
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function has_redirect()
    {
        $result     = null;
        $permission = null;

        // Evaluate if we even have the read permission
        if (!empty($this->_permissions['read'])) {
            $permission = $this->_permissions['read'];
        }

        if (!is_null($permission)) {
            if (!empty($permission['restriction_type'])) {
                $restriction_type = $permission['restriction_type'];
            } else {
                $restriction_type = null;
            }

            if ($restriction_type === 'redirect') {
                $result = $permission['effect'] === 'deny';
            }
        }

        return apply_filters('aam_post_has_redirect_filter', $result, $this);
    }

    /**
     * Get post password
     *
     * @return string|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_password()
    {
        if ($this->is_password_protected()) {
            $result = $this->_permissions['read']['password'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get content teaser message
     *
     * @return string|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_teaser_message()
    {
        if ($this->has_teaser_message()) {
            $result = $this->_permissions['read']['message'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get content redirect
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_redirect()
    {
        if ($this->has_redirect()) {
            $result = $this->_permissions['read']['redirect'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Initialize additional properties
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $post = get_post($this->_internal_id);

        if (is_a($post, 'WP_Post')) {
            $this->_core_instance = $post;
        } else {
            throw new OutOfRangeException(
                "Post with ID {$this->_internal_id} does not exist"
            );
        }
    }

    /**
     * Normalize permission model further
     *
     * @param array  $permission
     * @param string $permission_key
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _normalize_permission($permission, $permission_key)
    {
        if ($permission_key === 'list'
            && (!array_key_exists('on', $permission) || !is_array($permission['on']))
        ) {
            $permission['on'] = [
                'frontend',
                'backend',
                'api'
            ];
        }

        return $permission;
    }

    /**
     * Get settings namespace
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_settings_ns()
    {
        // Compile the namespace
        return constant('static::TYPE') . '.' . $this->get_internal_id(true);
    }

}