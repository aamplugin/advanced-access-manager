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
        try{
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'list');

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
                'aam_post_permission_result_filter',
                $result,
                $permission,
                $identifier,
                'list'
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
     * It is important to note that if user has the ability to edit a post, it can't
     * be password protected
     *
     * @param mixed $post_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.10
     */
    public function is_password_protected($post_identifier)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!current_user_can('edit_post', $identifier->ID)) {
                // First, let's check that current post does not have password set
                // natively
                $native_password = $identifier->post_password;
                $result          = !empty($native_password) ? true : null;

                if (is_null($result) && !is_null($permission)) {
                    if (!empty($permission['restriction_type'])) {
                        $restriction_type = $permission['restriction_type'];
                    } else {
                        $restriction_type = null;
                    }

                    if ($restriction_type === 'password_protected') {
                        $result = $permission['effect'] !== 'allow'
                            && !empty($permission['password']);
                    }
                }

                // Making sure that other implementations can affect the decision
                $result = apply_filters(
                    'aam_post_permission_result_filter',
                    $result,
                    $permission,
                    $identifier,
                    'read'
                );
            }

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
        try {
            $result      = null;
            $resource    = $this->_get_resource();
            $identifier  = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!empty($permission)) {
                $restriction_type = 'default';

                if (!empty($permission['restriction_type'])) {
                    $restriction_type = $permission['restriction_type'];
                }

                if ($permission['effect'] !== 'allow') {
                    if ($restriction_type === 'expire') {
                        $result = $this->_is_post_expired($permission);
                    } elseif ($restriction_type === 'default') {
                        $result = true;
                    }
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_permission_result_filter',
                $result,
                $permission,
                $identifier,
                'read'
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                if ($type === 'redirect') {
                    $result = $permission['effect'] !== 'allow';
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_permission_result_filter',
                $result,
                $permission,
                $identifier,
                'read'
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                if ($type === 'teaser_message') {
                    $result = $permission['effect'] !== 'allow';
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_permission_result_filter',
                $result,
                $permission,
                $identifier,
                'read'
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
        try {
            $result = null;

            if ($permission === 'read') {
                $result = $this->is_restricted($post_identifier);
            } else {
                $resource   = $this->_get_resource();
                $identifier = $this->_normalize_resource_identifier($post_identifier);
                $data       = $resource->get_permission($identifier, $permission);

                if (isset($data)) {
                    $result = $data['effect'] !== 'allow';
                }

                // Making sure that other implementations can affect the decision
                $result = apply_filters(
                    'aam_post_permission_result_filter',
                    $result,
                    $data,
                    $identifier,
                    $permission
                );
            }

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!empty($permission)) {
                $type = null;

                if (!empty($permission['restriction_type'])) {
                    $type = $permission['restriction_type'];
                }

                if ($type === 'expire') {
                    $result = $permission['effect'] !== 'allow'
                        && $this->_is_post_expired($permission);
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_post_permission_result_filter',
                $result,
                $permission,
                $identifier,
                'read'
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
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
     * @param bool   $exclude_authors [Optional] **Premium Feature**
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_password(
        $post_identifier,
        $password,
        $exclude_authors = false
    ) {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            $result   = $resource->set_permission($identifier, 'read', [
                'effect'           => 'deny',
                'restriction_type' => 'password_protected',
                'password'         => $password
            ], $exclude_authors);
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
     * @param bool   $exclude_authors [Optional] **Premium Feature**
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_teaser_message(
        $post_identifier,
        $message,
        $exclude_authors = false
    ) {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            $result = $resource->set_permission($identifier, 'read', [
                'effect'           => 'deny',
                'restriction_type' => 'teaser_message',
                'message'          => $message
            ], $exclude_authors);
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
     * @param bool  $exclude_authors [Optional] **Premium Feature**
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_redirect(
        $post_identifier,
        $redirect,
        $exclude_authors = false
    ) {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            $result = $resource->set_permission($identifier, 'read', [
                'effect'           => 'deny',
                'restriction_type' => 'redirect',
                'redirect'         => $redirect
            ], $exclude_authors);
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
     * @param bool  $exclude_authors [Optional] **Premium Feature**
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function set_expiration(
        $post_identifier,
        $timestamp,
        $exclude_authors = false
    ) {
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

            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            $result = $resource->set_permission($identifier, 'read', [
                'effect'           => 'deny',
                'restriction_type' => 'expire',
                'expires_after'    => intval($timestamp)
            ], $exclude_authors);
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
     * @param bool         $exclude_authors [Optional] **Premium Feature**
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($post_identifier, $permission, $exclude_authors = false)
    {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            if (is_string($permission)) {
                $result = $resource->set_permission(
                    $identifier, $permission, 'deny', $exclude_authors
                );
            } elseif (is_array($permission)) {
                $result = true;

                foreach($permission as $p) {
                    $result = $result && $resource->set_permission(
                        $identifier, $p, 'deny', $exclude_authors
                    );
                }
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
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);

            if (is_string($permission)) {
                $result = $resource->set_permission(
                    $identifier, $permission, 'allow'
                );
            } elseif (is_array($permission)) {
                $result = true;

                foreach($permission as $p) {
                    $result = $result && $resource->set_permission(
                        $identifier, $p, 'allow'
                    );
                }
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
     * @param bool         $exclude_authors [Optional] **Premiums Feature**
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function hide(
        $post_identifier,
        $website_area = null,
        $exclude_authors = false
    ) {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = [ 'effect' => 'deny' ];

            // Determine the list of areas for list permission
            if (is_string($website_area)) {
                $permission['on'] = [ trim($website_area) ];
            } elseif (is_array($website_area)) {
                $permission['on'] = array_map('trim', $website_area);
            }

            $result = $resource->set_permission(
                $identifier, 'list', $permission, $exclude_authors
            );
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
     * @version 7.0.5
     */
    public function show($post_identifier, $website_area = null)
    {
        try {
            $resource   = $this->_get_resource($post_identifier);
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = [ 'effect' => 'allow' ];

            // Determine the list of areas for list permission
            if (is_string($website_area)) {
                $permission['on'] = [ trim($website_area) ];
            } elseif (is_array($website_area)) {
                $permission['on'] = array_map('trim', $website_area);
            }

            $result = $resource->set_permission(
                $identifier, 'list', $permission
            );
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
            $resource    = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission  = $resource->get_permission($identifier, 'read');
            $native_pass = $identifier->post_password;

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
    public function get_teaser_message($post_identifier)
    {
        $result = null;

        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

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
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

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
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($post_identifier);
            $permission = $resource->get_permission($identifier, 'read');

            if (!empty($permission)
                && $permission['effect'] === 'deny'
                && !empty($permission['restriction_type'])
                && $permission['restriction_type'] === 'expire'
            ) {
                $result = intval($permission['expires_after']);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Aggregate all posts' permissions
     *
     * This method returns all explicitly defined permissions for all the posts. It
     * also includes permissions defined with JSON access policies, if the service
     * is enabled.
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function aggregate()
    {
        try {
            $result = $this->_get_resource()->get_permissions();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * Reset post permissions or permissions to all posts if $post_identifier is not
     * provided
     *
     * @param mixed $post_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($post_identifier = null)
    {
        try {
            if (!empty($post_identifier)) {
                $result = $this->_get_resource()->reset(
                    $this->_normalize_resource_identifier($post_identifier)
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
     * Get post resource
     *
     * @return AAM_Framework_Resource_Post
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::POST
        );
    }

    /**
     * @inheritDoc
     * @return WP_Post
     *
     * @version 7.0.6
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Post::class)) {
            $result = $resource_identifier;
        } elseif (is_numeric($resource_identifier)) {
            $result = get_post($resource_identifier);
        } elseif (is_array($resource_identifier)) {
            if (isset($resource_identifier['id'])) {
                $result = get_post($resource_identifier['id']);
            } else {
                // Let's get post_name
                if (isset($resource_identifier['slug'])) {
                    $post_name = $resource_identifier['slug'];
                } elseif (isset($resource_identifier['post_name'])) {
                    $post_name = $resource_identifier['post_name'];
                }

                if (!empty($post_name) && isset($resource_identifier['post_type'])) {
                    $result = $this->misc->get_post_by_slug(
                        $post_name,
                        $resource_identifier['post_type']
                    );
                }
            }

            // Do some additional validation if id & post_type are provided in the
            // array
            if (is_a($result, WP_Post::class)
                && isset($resource_identifier['post_type'])
                && $resource_identifier['post_type'] !== $result->post_type
            ) {
                throw new OutOfRangeException(
                    'The post_type does not match actual post type'
                );
            }
        }

        if (!is_a($result, WP_Post::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

    /**
     * Determine if post is expired
     *
     * @param array $permission
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_post_expired($permission)
    {
        if (isset($permission['expires_after'])) {
            $after = intval($permission['expires_after']);
        } else {
            $after = null;
        }

        return !empty($after) ? time() >= $after : false;
    }

}