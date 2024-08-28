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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

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

        return apply_filters('aam_post_is_password_protected_filter', $result, $this);
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
     * @inheritDoc
     */
    public function merge_permissions($permissions)
    {
        $result           = [];
        $base_permissions = $this->get_permissions();

        $permission_list = array_unique(
            [...array_keys($base_permissions), ...array_keys($permissions)]
        );

        $config = AAM::api()->configs();

        // Determine permissions merging preference
        $merging_preference = strtolower($config->get_config(
            'core.settings.' . self::TYPE . '.merge.preference',
            $config->get_config('core.settings.merge.preference')
        ));
        $default_effect = $merging_preference === 'allow' ? 'allow' : 'deny';

        foreach($permission_list as $perm) {
            $effect_a = null;
            $effect_b = null;

            if (isset($base_permissions[$perm])) {
                $effect_a = $base_permissions[$perm]['effect'];
            }

            if (isset($permissions[$perm])) {
                $effect_b = $permissions[$perm]['effect'];
            }

            if ($default_effect === 'allow') { // Merging preference is to allow
                if (in_array($effect_a, [ 'allow', null ], true)
                    || in_array($effect_b, [ 'allow', null ], true)
                ) {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                } elseif (!is_null($effect_b)) {
                    $result[$perm] = $permissions[$perm];
                } else {
                    $result[$perm] = $base_permissions[$perm];
                }
            } else { // Merging preference is to deny access by default
                if ($effect_b === 'deny') {
                    $result[$perm] = $permissions[$perm];
                } elseif ($effect_a === 'deny') {
                    $result[$perm] = $base_permissions[$perm];
                } else {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                }
            }
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

}