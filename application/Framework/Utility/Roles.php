<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Roles implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Get list of editable roles
     *
     * @return array Array of AAM_Framework_AccessLevel_Role
     * @access public
     *
     * @version 7.0.0
     */
    public function get_editable_roles()
    {
        $result = [];
        $roles  = wp_roles();

        if (function_exists('get_editable_roles')) {
            $all = get_editable_roles();
        } else {
            $all = apply_filters('editable_roles', $roles->roles);
        }

        foreach(array_keys($all) as $slug) {
            array_push($result, AAM::api()->role($slug));
        }

        return $result;
    }

    /**
     * Check if role is editable
     *
     * @param string $slug
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_editable_role($slug)
    {
        if (function_exists('get_editable_roles')) {
            $all = get_editable_roles();
        } else {
            $all = apply_filters('editable_roles', wp_roles()->roles);
        }

        // Making sure that all role slugs are string. It is possible that some
        // role names are just numbers
        $slugs = array_map('trim', array_keys($all));

        return in_array($slug, $slugs, true);
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
     * @param string $display_name
     * @param string $slug               [Optional] Role slug
     * @param array  $capabilities       [Optional] Array of capabilities
     * @param bool   $ignore_caps_format [Optional]
     *
     * @return AAM_Framework_AccessLevel_Role
     * @access public
     *
     * @version 7.0.0
     */
    public function create(
        $display_name,
        $slug = null,
        array $capabilities = [],
        $ignore_caps_format = false
    ) {
        $name  = sanitize_text_field($display_name);
        $roles = wp_roles();

        if (!is_string($name) || strlen($name) === 0) {
            throw new InvalidArgumentException('Role name is invalid');
        }

        // Verify that if role slug is provided and it is valid
        if (is_string($slug) && strlen($slug) > 0) {
            $slug = sanitize_key($slug);

            if (strlen($slug) === 0) {
                throw new InvalidArgumentException('Role slug is invalid');
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
        $caps = array_unique(array_map(function($cap) use ($ignore_caps_format) {
            if (!$ignore_caps_format) {
                $cap = sanitize_key($cap);
            }

            if (!is_string($cap) || strlen($cap) === 0) {
                throw new InvalidArgumentException("Capability '{$cap}' is invalid");
            }

            return $cap;
        }, $capabilities));

        // Creating new role
        $roles->add_role($slug, $name, array_fill_keys($caps, true));

        return AAM::api()->role($slug);
    }

    /**
     * Update role attributes
     *
     * @param string $slug
     * @param array  $data [Optional]
     *
     * @return AAM_Framework_AccessLevel_Role
     * @access public
     *
     * @version 7.0.0
     */
    public function update($slug, array $data = [])
    {
        $role = AAM::api()->role($slug);

        if ($role->update($data)) {
            $result = $role;
        } else {
            throw new RuntimeException('Failed to persist changes');
        }

        return $result;
    }

    /**
     * Delete existing role
     *
     * @param string $slug
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function delete($slug)
    {
        $role = AAM::api()->role($slug);

        // Verifying that role has not users assigned. Otherwise reject
        if ($role->user_count > 0) {
            throw new LogicException('Cannot delete role with users');
        }

        // Delete the role
        wp_roles()->remove_role($role->slug);

        return !wp_roles()->is_role($role->slug);
    }

}