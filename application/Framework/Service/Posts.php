<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Posts framework service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Posts
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Determine if post is hidden on given area
     *
     * @param mixed  $post_identifier
     * @param string $website_area    Can be either frontend, backend or api
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_hidden_on($post_identifier, $website_area)
    {
        $result = false;

        try{
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['list'];

            if (!empty($permission)) {
                if ($permission['effect'] === 'deny') {
                    if (isset($permission['on']) && is_array($permission['on'])) {
                        $result = in_array($website_area, $permission['on'], true);
                    } else {
                        $result = true;
                    }
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_hidden_on_filter',
                $result,
                $website_area,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if post is hidden on currently viewed area
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_hidden($post_identifier)
    {
        return $this->is_hidden_on(
            $post_identifier, $this->misc->get_current_area()
        );
    }

    /**
     * Determine if current post is password protected
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_password_protected($post_identifier)
    {
        $result = false;

        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['read'];

            // First, let's check that current post does not have password set
            // natively
            $native_password = $resource->post_password;
            $result          = !empty($native_password);

            if (!$result && !is_null($permission)) {
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

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_password_protected_filter',
                $result,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Façade function that determines if post is restricted for direct access
     *
     * This method verifies is post is set as restricted by checking following:
     * - Post is set as restricted without any additional conditions;
     * - Post access is expired
     * - The aam_post_is_restricted_filter returns positive result
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_restricted($post_identifier)
    {
        $result = false;

        try {
            $result     = false;
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['read'];

            if (!empty($permission)) {
                $restriction_type = 'default';

                if (!empty($permission['restriction_type'])) {
                    $restriction_type = $permission['restriction_type'];
                }

                if ($permission['effect'] === 'deny') {
                    if ($restriction_type === 'expire') {
                        $result = $this->_is_post_expired($resource);
                    } elseif ($restriction_type === 'default') {
                        $result = true;
                    }
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_restricted_filter',
                $result,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if current post is redirected elsewhere
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_redirected($post_identifier)
    {
        $result = false;

        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['read'];

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                $result = $permission['effect'] === 'deny' && $type === 'redirect';
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_redirected_filter',
                $result,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if teaser message is defined for the post
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_teaser_message_set($post_identifier)
    {
        $result = false;

        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['read'];

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                $result = $permission['effect'] === 'deny'
                                                && $type === 'teaser_message';
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_teaser_message_set_filter',
                $result,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Façade function that determines if access level does not have certain
     * permission
     *
     * @param mixed  $post_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied_to($post_identifier, $permission)
    {
        $result = false;

        try {
            $resource = $this->_get_resource($post_identifier);

            if ($permission === 'read') {
                $result = $this->is_restricted($post_identifier);
            } else {
                if (isset($resource[$permission])) {
                    $result = $resource[$permission]['effect'] !== 'allow';
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_is_denied_to_filter',
                $result,
                $permission,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Façade function that determines if access level has certain permission
     *
     * @param mixed  $post_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed_to($post_identifier, $permission)
    {
        $decision = $this->is_denied_to($post_identifier, $permission);

        return is_bool($decision) ? !$decision : $decision;
    }

    /**
     * Check if there is an active expiration date defined
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_access_expired($post_identifier)
    {
        $result = false;

        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = $resource['read'];

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                $result = $permission['effect'] === 'deny'
                    && $type === 'expire'
                    && $this->_is_post_expired($resource);
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_has_expiration_filter',
                $result,
                $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set password to protect a post
     *
     * @param mixed  $post_identifier
     * @param string $password
     * @param bool   $use_native_pass [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_password(
        $post_identifier,
        $password,
        $use_native_pass = false
    ) {
        try {
            $resource = $this->_get_resource($post_identifier);

            if ($use_native_pass) {
                // Update post record
                $result = wp_update_post([
                    'ID'            => $resource->ID,
                    'post_password' => $password
                ]);
            } else {
                $result = $resource->add_permission('read', [
                    'effect'           => 'deny',
                    'restriction_type' => 'password_protected',
                    'password'         => $password
                ]);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        // Normalizing the wp_update_post response
        return is_int($result) ? true : $result;
    }

    /**
     * Set teaser message for a post
     *
     * @param mixed  $post_identifier
     * @param string $message
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_teaser_message($post_identifier, $message)
    {
        try {
            $result = $this->_get_resource($post_identifier)->add_permission('read', [
                'effect'           => 'deny',
                'restriction_type' => 'teaser_message',
                'message'          => $message
            ]);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set redirect
     *
     * @param mixed $post_identifier
     * @param array $redirect
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect($post_identifier, $redirect)
    {
        try {
            $result = $this->_get_resource($post_identifier)->add_permission('read', [
                'effect'           => 'deny',
                'restriction_type' => 'redirect',
                'redirect'         => $redirect
            ]);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Set post read expiration
     *
     * Direct access to the post will be ceased (denied) after provided timestamp
     *
     * @param mixed $post_identifier
     * @param int   $timestamp
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_expiration($post_identifier, $timestamp)
    {
        try {
            if (!is_numeric($timestamp)) {
                throw new InvalidArgumentException(
                    'The expiration has to be a valid Unix Timestamp'
                );
            } elseif ($timestamp < time()) {
                throw new InvalidArgumentException(
                    'The expiration has to be in the future'
                );
            }

            $result = $this->_get_resource($post_identifier)->add_permission('read', [
                'effect'           => 'deny',
                'restriction_type' => 'expire',
                'expires_after'    => intval($timestamp)
            ]);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Deny one or multiple permissions
     *
     * @param mixed        $post_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($post_identifier, $permission)
    {
        try {
            $resource = $this->_get_resource($post_identifier);

            if (is_string($permission)) {
                $result = $resource->add_permission($permission, 'deny');
            } elseif (is_array($permission)) {
                $result = $resource->add_permissions($permission, 'deny');
            } else {
                throw new InvalidArgumentException('Invalid permission type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow one or multiple permissions
     *
     * @param mixed        $post_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($post_identifier, $permission)
    {
        try {
            $resource = $this->_get_resource($post_identifier);

            if (is_string($permission)) {
                $result = $resource->add_permission($permission, 'allow');
            } elseif (is_array($permission)) {
                $result = $resource->add_permissions($permission, 'allow');
            } else {
                throw new InvalidArgumentException('Invalid permission type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Hide post on the given areas
     *
     * If the $website_area param is not provided, the post will be hidden on all
     * areas
     *
     * @param mixed        $post_identifier
     * @param string|array $website_area    [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function hide($post_identifier, $website_area = null)
    {
        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = [
                'effect' => 'deny'
            ];

            // Determine the list of areas for list permission
            if (is_string($website_area)) {
                $permission['on'] = [ trim($website_area) ];
            } elseif (is_array($website_area)) {
                $permission['on'] = array_map('trim', $website_area);
            }

            $result = $resource->add_permission('list', $permission);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Show post on the given areas
     *
     * If the $website_area param is not provided, the post will be visible on all
     * areas. Otherwise, the "list" permission will be set as "deny" only for the
     * specified areas.
     *
     * @param mixed        $post_identifier
     * @param string|array $website_area    [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function show($post_identifier, $website_area = null)
    {
        try {
            $resource   = $this->_get_resource($post_identifier);
            $permission = [
                'effect' => 'deny'
            ];

            // Determine the list of areas for list permission
            if (is_string($website_area)) {
                $on = [ trim($website_area) ];
            } elseif (is_array($website_area)) {
                $on = array_map('trim', $website_area);
            } else {
                $on = null;
            }

            if (is_null($on) || count($on) === 3) {
                $result = $resource->remove_permission('list');
            } else {
                $permission['on'] = array_diff(
                    [ 'frontend', 'backend', 'api' ], $on
                );

                $result = $resource->add_permission('list', $permission);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get post password
     *
     * @param mixed $post_identifier
     *
     * @return string|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_password($post_identifier)
    {
        $result = null;

        try {
            $resource    = $this->_get_resource($post_identifier);
            $permission  = $resource['read'];
            $native_pass = $resource->post_password;

            if (!empty($native_pass)) {
                $result = $native_pass;
            } elseif (!empty($permission)
                && $permission['effect'] === 'deny'
                && !empty($permission['restriction_type'])
                && $permission['restriction_type'] === 'password_protected'
                && !empty($permission['password'])
            ) {
                $result = $permission['password'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get post teaser message
     *
     * @param mixed $post_identifier
     *
     * @return string|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_teaser_message(mixed $post_identifier)
    {
        $result = null;

        try {
            $resource    = $this->_get_resource($post_identifier);
            $permission  = $resource['read'];

            if (!empty($permission)
                && $permission['effect'] === 'deny'
                && !empty($permission['restriction_type'])
                && $permission['restriction_type'] === 'teaser_message'
            ) {
                $result = $permission['message'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get post redirect
     *
     * @param mixed $post_identifier
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_redirect($post_identifier)
    {
        $result = null;

        try {
            $resource    = $this->_get_resource($post_identifier);
            $permission  = $resource['read'];

            if (!empty($permission)
                && $permission['effect'] === 'deny'
                && !empty($permission['restriction_type'])
                && $permission['restriction_type'] === 'redirect'
            ) {
                $result = $permission['redirect'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get post expiration timestamp
     *
    * @param mixed $post_identifier
     *
     * @return int|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_expiration($post_identifier)
    {
        $result = null;

        try {
            $resource    = $this->_get_resource($post_identifier);
            $permission  = $resource['read'];

            if (!empty($permission)
                && $permission['effect'] === 'deny'
                && !empty($permission['restriction_type'])
                && $permission['restriction_type'] === 'expire'
            ) {
                $result = intval($permission['expire_after']);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get post resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($identifier)
    {
        // Allow polymorphism - when other resource type acts like post resource.
        // This is useful tactic to cover scenarios where resources like Term or
        // Post Type hold permissions for their child posts
        if (is_a($identifier, AAM_Framework_Resource_Interface::class)) {
            $result = $identifier;
        } else {
            $post = null;

            // Determining if we are dealing with post ID or post slug
            if (is_numeric($identifier)) {
                // Fetching post by ID
                $post = get_post(intval($identifier));
            } elseif (is_array($identifier)) {
                if (isset($identifier['id'])) {
                    $post = get_post($identifier['id']);
                } else {
                    // Let's get post_name
                    if (isset($identifier['slug'])) {
                        $post_name = $identifier['slug'];
                    } elseif (isset($identifier['post_name'])) {
                        $post_name = $identifier['post_name'];
                    }

                    if (!empty($post_name) && isset($identifier['post_type'])) {
                        $post = get_page_by_path(
                            $post_name,
                            OBJECT,
                            $identifier['post_type']
                        );
                    }
                }
            } elseif (is_a($identifier, WP_Post::class)) {
                $post = $identifier;
            }

            if (!is_a($post, 'WP_Post')) {
                throw new OutOfRangeException(
                    'Cannot get post instance based on provided post identifier'
                );
            }

            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::POST, $post
            );
        }

        return $result;
    }

    /**
     * Determine if post is expired
     *
     * @param AAM_Framework_Resource_Post $post
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_post_expired($post)
    {
        $perm  = $post['read'];
        $after = isset($perm['expires_after']) ? intval($perm['expires_after']) : null;

        return !empty($after) ? time() >= $after : false;
    }

}