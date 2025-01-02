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
class AAM_Framework_Resource_Post implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait, AAM_Framework_Resource_ContentTrait {
        AAM_Framework_Resource_ContentTrait::_get_settings_ns insteadof AAM_Framework_Resource_BaseTrait;
        AAM_Framework_Resource_ContentTrait::_normalize_permission insteadof AAM_Framework_Resource_BaseTrait;
    }

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * @inheritDoc
     */
    const AGGREGATABLE = true;

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

        return apply_filters('aam_post_is_hidden_on_filter', $result, $area, $this);
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
        return $this->is_hidden_on(
            AAM_Framework_Manager::_()->misc->get_current_area()
        );
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
        $native_password = $this->post_password;
        $result          = !empty($native_password) ? true : null;

        if (is_null($result)) {
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
        }

        return apply_filters(
            'aam_post_is_password_protected_filter',
            $result,
            $this
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
                if ($restriction_type === 'default' || $this->is_expired()) {
                    $result = true;
                }
            } else {
                $result = false;
            }
        }

        return apply_filters('aam_post_is_restricted_filter', $result, $this);
    }

    /**
     * Determine if current post has redirect defined
     *
     * @return boolean|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_redirected()
    {
        return $this->_has('redirect');
    }

    /**
     * Façade function that determines if access level does not have certain
     * permission
     *
     * @param string $permission
     *
     * @return bool|null
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
     * Façade function that determines if access level has certain permission
     *
     * @param string $permission
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to($permission)
    {
        $decision = $this->is_denied_to($permission);

        return is_bool($decision) ? !$decision : $decision;
    }

    /**
     * Determine if current post has teaser message
     *
     * Instead of a post's content, the specified teaser message is displayed
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_teaser_message_set()
    {
        return $this->_has('teaser_message');
    }

    /**
     * Alias to the is_teaser_message_set
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_teased()
    {
        return $this->is_teaser_message_set();
    }

    /**
     * Check if there is an active expiration date defined
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_expiration_set()
    {
        return $this->_has('expire');
    }

    /**
     * Determine if post has expiration time defined and access is expired
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_expired()
    {
        if ($this->is_expiration_set()) {
            $permission = $this->_permissions['read'];
            $result     = time() >= intval($permission['expires_after']);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Set password to protect a post
     *
     * @param string $password
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_password($password)
    {
        return $this->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'password_protected',
            'password'         => $password
        ]);
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
            $native_password = $this->post_password;

            if (!empty($native_password)) {
                $result = $native_password;
            } else {
                $result = $this->_permissions['read']['password'];
            }
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Set teaser message for a post
     *
     * @param string $message
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_teaser_message($message)
    {
        return $this->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'teaser_message',
            'message'          => $message
        ]);
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
        if ($this->is_teased()) {
            $result = $this->_permissions['read']['message'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Set redirect
     *
     * @param array $redirect
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect($redirect)
    {
        return $this->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'redirect',
            'redirect'         => $redirect
        ]);
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
        if ($this->is_redirected()) {
            $result = $this->_permissions['read']['redirect'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Set post read expiration
     *
     * Direct access to the post will be ceased (denied) after provided timestamp
     *
     * @param int $timestamp
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_expiration($timestamp)
    {
        if (!is_numeric($timestamp)) {
            throw new InvalidArgumentException(
                'The expiration has to be a valid Unix Timestamp'
            );
        } elseif ($timestamp < time()) {
            throw new InvalidArgumentException(
                'The expiration has to be in the future'
            );
        }

        return $this->add_permission('read', [
            'effect'           => 'deny',
            'restriction_type' => 'expire',
            'expires_after'    => intval($timestamp)
        ]);
    }

    /**
     * Get expiration
     *
     * @return int|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_expiration()
    {
        if ($this->is_expiration_set()) {
            $result = $this->_permissions['read']['expires_after'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Initialize additional properties
     *
     * @param mixed $resource_identifier
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        if (is_a($resource_identifier, WP_Post::class)) {
            $post = $resource_identifier;
        } elseif (is_numeric($resource_identifier)) {
            $post = get_post($resource_identifier);
         } elseif (is_array($resource_identifier)
            && isset($resource_identifier['id'])
        ) {
            $post = get_post($resource_identifier['id']);

            // Do some additional validation if id & post_type are provided in the
            // array
            if (is_a($post, WP_Post::class)
                && isset($resource_identifier['post_type'])
                && $resource_identifier['post_type'] !== $post->post_type
            ) {
                throw new OutOfRangeException(
                    'The resource identifier post_type does not match actual post type'
                );
            }
        }

        if (is_a($post, WP_Post::class)) {
            $this->_core_instance = $post;
            $this->_internal_id   = [
                'id'        => $post->ID,
                'post_type' => $post->post_type
            ];
        } else {
            throw new OutOfRangeException('The post resource identifier is invalid');
        }
    }

    /**
     * Check if certain restriction type is defined and denied
     *
     * @param string $restriction_type
     *
     * @return bool
     *
     * @access private
     * @version 7.0.0
     */
    private function _has($restriction_type)
    {
        $result     = null;
        $permission = null;

        // Evaluate if we even have the read permission
        if (!empty($this->_permissions['read'])) {
            $permission = $this->_permissions['read'];
        }

        if (!is_null($permission)) {
            if (!empty($permission['restriction_type'])) {
                $type = $permission['restriction_type'];
            } else {
                $type = null;
            }

            if ($type === $restriction_type) {
                $result = $permission['effect'] === 'deny';
            }
        }

        // TODO: Do not like this hook

        return apply_filters(
            "aam_post_has_{$restriction_type}_filter",
            $result,
            $this
        );
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        // Fetching all resources that may represent our current post and doing some
        // additional validation as the same post can be targeted by both ID & slug
        $by_id   = $service->statements("Post:{$this->post_type}:{$this->ID}");
        $by_slug = $service->statements("Post:{$this->post_type}:{$this->post_name}");

        if (!empty($by_id) && !empty($by_slug)) {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'Found the same post by ID & slug. May lead to unexpected results.',
                AAM_VERSION
            );
        }

        foreach(array_merge($by_id, $by_slug) as $stm) {
            $permissions = array_replace(
                $manager->policy->statement_to_permission($stm, 'post'),
                $permissions
            );
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}