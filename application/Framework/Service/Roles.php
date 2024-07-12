<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service role manage
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/400
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/275
 * @since 6.9.6  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_Roles
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * WP core roles object
     *
     * @var WP_Roles
     *
     * @access private
     * @version 6.9.6
     */
    private $_wp_roles = null;

    /**
     * Return list of roles
     *
     * @return array Array of AAM_Framework_Proxy_Role
     *
     * @access public
     * @version 6.9.6
     */
    public function get_all_roles(array $inline_context = [])
    {
        try {
            $result = [];
            $roles  = $this->_get_wp_roles();

            foreach($roles->role_objects as $role) {
                array_push($result, new AAM_Framework_Proxy_Role(
                    $roles->role_names[$role->name],
                    $role
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of editable roles
     *
     * @return array Array of AAM_Framework_Proxy_Role
     *
     * @access public
     * @version 6.9.6
     */
    public function get_editable_roles(array $inline_context = [])
    {
        try {
            $result = array();
            $roles  = $this->_get_wp_roles();

            if (function_exists('get_editable_roles')) {
                $all = get_editable_roles();
            } else {
                $all = apply_filters('editable_roles', $roles->roles);
            }

            foreach(array_keys($all) as $slug) {
                array_push($result, new AAM_Framework_Proxy_Role(
                    $roles->role_names[$slug],
                    $roles->get_role($slug)
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get role by slug
     *
     * @param string  $slug  Unique role slug (aka ID)
     *
     * @return AAM_Framework_Proxy_Role|null
     *
     * @access public
     * @throws OutOfRangeException
     * @version 6.9.6
     */
    public function get_role($slug, array $inline_context = [])
    {
        try {
            $all   = $this->get_all_roles();
            $match = array_filter($all, function($role) use ($slug) {
                return $role->slug === $slug;
            });

            if (count($match) === 0) {
                throw new OutOfRangeException(
                    "Role '{$slug}' does not exist or is not editable"
                );
            }

            $result = array_shift($match);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if provided slug is a role
     *
     * @param string $slug
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.33
     */
    public function is_role($slug, array $inline_context = [])
    {
        try {
            $result = $this->_get_wp_roles()->is_role($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if role is editable
     *
     * @param string $slug
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/400
     * @since 6.9.33 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function is_editable_role($slug, array $inline_context = [])
    {
        try {
            $roles = $this->_get_wp_roles();

            if (function_exists('get_editable_roles')) {
                $editable_roles = get_editable_roles();
            } else {
                $editable_roles = apply_filters('editable_roles', $roles->roles);
            }

            // Making sure that all role slugs are string. It is possible that some
            // role names are just numbers
            $slugs  = array_map('trim', array_keys($editable_roles));
            $result = in_array($slug, $slugs, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Create new role
     *
     * The method sanitizes and validates all the input values before creating a new
     * role. Additionally, verifies that there is no slug overlap with any existing
     * roles. In case of any validation issues, the method throws the
     * InvalidArgumentException exception.
     *
     * The only required argument is `$displayName`. If `$slug` is not provided, the
     * random number is generated with `uniqid` function.
     *
     * @param string $display_name Role name
     * @param string $slug         optional Role slug
     * @param array  $capabilities optional Array of capabilities
     *
     * @return AAM_Framework_Proxy_Role
     * @throws InvalidArgumentException
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/275
     * @since 6.9.6  Initial implementation of the method
     *
     * @access public
     * @version 6.9.10
     */
    public function create_role(
        $display_name,
        $slug = null,
        array $capabilities = [],
        array $inline_context = []
    ) {
        try {
            $name  = sanitize_text_field($display_name);
            $roles = $this->_get_wp_roles();

            if (is_string($name) && strlen($name) > 0) {
                // Verify that if role slug is provided and it is valid
                if (is_string($slug) && strlen($slug) > 0) {
                    $slug = sanitize_key($slug);

                    if (strlen($slug) === 0) {
                        throw new InvalidArgumentException(
                            'Role slug is invalid'
                        );
                    }
                } else {
                    // First, try to normalize the roles name into slug and if
                    // nothing, then generate the random number
                    $slug = str_replace(' ', '_', sanitize_key($name));
                    $slug = empty($slug) ? strtolower(uniqid()) : $slug;
                }

                if ($roles->is_role($slug)) {
                    throw new LogicException("Role {$slug} already exists");
                }

                // Sanitize the list of capabilities and make sure that the list
                // contains unique caps

                $caps = array_unique(array_map(function($cap) {
                    $result = sanitize_key($cap);

                    if (!is_string($result) || strlen($result) === 0) {
                        throw new InvalidArgumentException(
                            "Capability '{$cap}' is invalid"
                        );
                    }

                    return $result;
                }, $capabilities));

                // Creating new role
                $result = new AAM_Framework_Proxy_Role(
                    $name,
                    $roles->add_role($slug, $name, array_fill_keys($caps, true))
                );
            } else {
                throw new InvalidArgumentException('Role name is invalid');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update role attributes
     *
     * @param AAM_Framework_Proxy_Role $role
     *
     * @return AAM_Framework_Proxy_Role
     *
     * @access public
     * @version 6.9.6
     */
    public function update_role(
        $slug, array $data = [], array $inline_context = []
    ) {
        try {
            $role = $this->get_role($slug, $inline_context);

            if ($role->update($data)) {
                $result = $role;
            } else {
                throw new RuntimeException('Failed to persist changes');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete existing role
     *
     * @param string $slug
     *
     * @return boolean
     *
     * @access public
     * @throws LogicException
     * @version 6.9.6
     */
    public function delete_role($slug, array $inline_context = []
    ) {
        try {
            $roles = $this->_get_wp_roles();
            $role  = $this->get_role($slug, $inline_context);

            // Verifying that role has not users assigned. Otherwise reject
            if ($role->user_count > 0) {
                throw new LogicException('Cannot delete role with users');
            }

            $roles->remove_role($role->slug);

            $result = !$roles->is_role($role->slug);

            if ($result === false) {
                throw new RuntimeException('Failed to delete role');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get the list of WordPress roles as WP_Roles object
     *
     * @return WP_Roles
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_wp_roles()
    {
        global $wp_roles;

        if (is_null($this->_wp_roles)) {
            if (function_exists('wp_roles')) {
                $this->_wp_roles = wp_roles();
            } elseif (isset($wp_roles)) {
                $this->_wp_roles = $wp_roles;
            } else {
                $this->_wp_roles = new WP_Roles();
            }
        }

        return $this->_wp_roles;
    }

}