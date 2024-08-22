<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Capabilities
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Framework_Service_Capabilities
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get all capabilities
     *
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.33
     */
    public function get_all_capabilities(array $inline_context = [])
    {
        try {
            // Now, just get capability slugs and prepare capability models
            $result = [];

            foreach($this->_prepare_all_role_capabilities() as $slug) {
                $result[$slug] = $this->_prepare_capability($slug);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get role capabilities
     *
     * @param string  $role_slug
     * @param boolean $return_all
     * @param array   $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.33
     */
    public function get_role_capabilities(
        $role_slug, $return_all = false, array $inline_context = []
    ) {
        try {
            $roles = wp_roles()->role_objects;

            // Get the list of capabilities that are explicitly granted to give role
            if (array_key_exists($role_slug, $roles)) {
                $role_caps = wp_roles()->role_objects[$role_slug]->capabilities;

                if ($return_all) {
                    $result = array_map(function($c) {
                        return array_merge($c, [
                            'is_assigned' => false,
                            'is_granted'  => false
                        ]);
                    }, $this->get_all_capabilities($inline_context));
                } else {
                    $result = [];
                }

                foreach($role_caps as $slug => $granted) {
                    $result[$slug] = $this->_prepare_capability(
                        $slug, $granted, true
                    );
                }
            } else {
                throw new OutOfRangeException("Role {$role_slug} does not exist");
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get user capabilities
     *
     * @param mixed   $user_identifier
     * @param boolean $return_all
     * @param array   $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.33
     */
    public function get_user_capabilities(
        $user_identifier, $return_all = false, array $inline_context = []
    ) {
        try {
            $user = $this->_get_user_by_identifier($user_identifier);

            // Get the list of capabilities that are explicitly granted to give role
            if ($return_all) {
                $result = array_map(function($c) {
                    return array_merge($c, [
                        'is_assigned' => false,
                        'is_granted'  => false
                    ]);
                }, $this->get_all_capabilities($inline_context));
            } else {
                $result = [];
            }

            foreach(array_keys($user->allcaps) as $slug) {
                $result[$slug] = $this->_prepare_capability(
                    $slug, $user->has_cap($slug), isset($user->caps[$slug])
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if capability exists
     *
     * @param string     $capability
     * @param string     $assignee_type
     * @param string|int $assignee_id
     * @param array      $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.33
     */
    public function exists(
        $capability,
        $assignee_type = null,
        $assignee_id = null,
        array $inline_context = []
    ) {
        try {
            // If there no assignee type, combine all the role capabilities + current
            // user capabilities
            if ($assignee_type === null) {
                $all_caps = array_merge(
                    $this->_prepare_all_role_capabilities(),
                    array_keys(wp_get_current_user()->caps)
                );
            } elseif ($assignee_type === 'role') {
                if (wp_roles()->is_role($assignee_id)) {
                    $all_caps = array_keys(
                        wp_roles()->role_objects[$assignee_id]->capabilities
                    );
                } else {
                    throw new OutOfRangeException(
                        "Role {$assignee_id} does not exist"
                    );
                }
            } elseif ($assignee_type === 'user') {
                $user     = $this->_get_user_by_identifier($assignee_id);
                $all_caps = array_keys($user->caps);
            }

            $result = in_array($capability, $all_caps, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Create new capability
     *
     * Assign newly created capability to the "Administrator" role and role that
     * was explicitly stated in the $assign_to_role
     *
     * @param string     $capability
     * @param string     $assignee_type
     * @param string|int $assignee_id
     * @param array      $inline_context
     *
     * @return string
     *
     * @access public
     * @version 6.9.33
     */
    public function create(
        $capability,
        $assignee_type = null,
        $assignee_id = null,
        array $inline_context = []
    ) {
        try {
            $slug = trim($capability);

            if (empty($slug)) {
                throw new InvalidArgumentException(
                    "The capability {$capability} is not valid"
                );
            }

            $result = $this->add_to_role(
                'administrator', $slug, $inline_context
            );

            switch ($assignee_type) {
                case 'role':
                    $result = $this->add_to_role(
                        $assignee_id, $slug, $inline_context
                    );
                    break;

                case 'user':
                    $result = $this->add_to_user(
                        $assignee_id, $slug, $inline_context
                    );
                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Replace old capability with new
     *
     * @param string     $old_capability
     * @param string     $new_capability
     * @param string     $assignee_type
     * @param string|int $assignee_id
     * @param array      $inline_context
     *
     * @return string
     *
     * @access public
     * @version 6.9.33
     */
    public function update(
        $old_capability,
        $new_capability,
        $assignee_type = null,
        $assignee_id = null,
        array $inline_context = []
    ) {
        try {
            $slug = trim($new_capability);

            if (empty($slug)) {
                throw new InvalidArgumentException(
                    "The capability slug {$new_capability} is not valid"
                );
            } elseif ($this->exists(
                $slug, $assignee_type, $assignee_id, $inline_context
            )) {
                throw new InvalidArgumentException(
                    "The {$slug} capability already exists"
                );
            }

            // Determine the scope of changes
            if ($assignee_type === null) {
                $updates = $this->_update_capability_for_all_roles(
                    $old_capability, $slug
                );

                if ($updates === 0) {
                    throw new OutOfRangeException(
                        "The capability {$old_capability} does not exist"
                    );
                }

                $result = $this->_prepare_capability($slug);
            } elseif ($assignee_type === 'role') {
                $result = $this->_update_capability_for_role(
                    $old_capability, $slug, $assignee_id
                );
            } elseif ($assignee_type === 'user') {
                $result = $this->_update_capability_for_user(
                    $old_capability, $slug, $assignee_id
                );
            } else {
                throw new InvalidArgumentException(
                    "The assignee type {$assignee_type} is invalid"
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete existing capability
     *
     * @param string     $capability
     * @param string     $assignee_type
     * @param string|int $assignee_id
     * @param array      $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.33
     */
    public function delete(
        $capability,
        $assignee_type = null,
        $assignee_id = null,
        array $inline_context = []
    ) {
        try {
            if (empty($capability)) {
                throw new InvalidArgumentException(
                    "The capability slug {$capability} is not valid"
                );
            }

            // Determine the scope of changes
            if ($assignee_type === null) {
                $updates = $this->_delete_capability_from_all_roles($capability);
            } elseif ($assignee_type === 'role') {
                $updates = $this->_delete_capability_from_role(
                    $capability, $assignee_id
                );
            } elseif ($assignee_type === 'user') {
                $updates = $this->_delete_capability_from_user(
                    $capability, $assignee_id
                );
            } else {
                throw new InvalidArgumentException(
                    "The assignee type {$assignee_type} is invalid"
                );
            }

            if (empty($updates)) {
                throw new OutOfRangeException(
                    "The capability {$capability} does not exist"
                );
            }

            $result = true;
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Add capability to the role
     *
     * @param string $role_slug
     * @param string $capability
     * @param array  $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.33
     */
    public function add_to_role(
        $role_slug, $capability, array $inline_context = []
    ) {
        try {
            $roles = wp_roles();

            if (array_key_exists($role_slug, $roles->role_objects)) {
                $role = $roles->role_objects[$role_slug];

                $role->add_cap($capability);
            } else {
                throw new OutOfRangeException(
                    "Role {$role_slug} does not exist"
                );
            }

            $result = $this->_prepare_capability($capability, true, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Add capability to the user
     *
     * @param mixed  $user_identifier
     * @param string $capability
     * @param array  $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.33
     */
    public function add_to_user(
        $user_identifier, $capability, array $inline_context = []
    ) {
        try {
            $user = $this->_get_user_by_identifier($user_identifier);

            $user->add_cap($capability);

            $result = $this->_prepare_capability($capability, true, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get user by its identifier
     *
     * @param mixed $identifier
     *
     * @return WP_User
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_user_by_identifier($identifier)
    {
        if (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier)) {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        } elseif (is_a($identifier, 'WP_User')) {
            $user = $identifier;
        } else {
            $user = false;
        }

        if (!is_a($user, 'WP_User')){
            throw new OutOfRangeException(
                'The user identifier is invalid or user does not exist'
            );
        }

        return $user;
    }

    /**
     * Prepare capability model
     *
     * @param string  $slug
     * @param boolean $is_granted
     * @param boolean $is_assigned
     *
     * @return array
     *
     * @access private
     * @version 6.9.33
     */
    private function _prepare_capability(
        $slug, $is_granted = null, $is_assigned = null
    ) {
        $result = [
            'slug'        => $slug,
            'description' => apply_filters(
                'aam_capability_description_filter', null, $slug
            )
        ];

        if ($is_granted !== null) {
            $result['is_granted'] = $is_granted;
        }

        if ($is_assigned !== null) {
            $result['is_assigned'] = $is_assigned;
        }

        return $result;
    }

    /**
     * Iterate over all roles and update capability
     *
     * @param string $old_slug
     * @param string $new_slug
     *
     * @return int
     *
     * @access private
     * @version 6.9.33
     */
    private function _update_capability_for_all_roles($old_slug, $new_slug)
    {
        $updates = 0; // Count number of updates

        // Iterating of the list of all roles and update old cap with new
        foreach(wp_roles()->role_objects as $role) {
            // Increment the number of updates so we know that something was
            // actually changed
            try {
                $this->_update_capability_for_role(
                    $old_slug, $new_slug, $role->name
                );

                $updates++;
            } catch (OutOfRangeException $e) {
                // Do nothing. This is expected behavior if role does not have
                // a capability
            }
        }

        return $updates;
    }

    /**
     * Update capability for role
     *
     * @param string $old_slug
     * @param string $new_slug
     * @param string $role_slug
     *
     * @return array|boolean
     *
     * @access private
     * @version 6.9.33
     */
    private function _update_capability_for_role($old_slug, $new_slug, $role_slug)
    {
        if (!wp_roles()->is_role($role_slug)) {
            throw new OutOfRangeException("Role {$role_slug} does not exist");
        }

        $role = wp_roles()->role_objects[$role_slug];

        if (array_key_exists($old_slug, $role->capabilities)) {
            // Persist the granted flag
            $granted = $role->capabilities[$old_slug];

            // Use core functions to add/remove cap to take into consideration
            // setups were roles and capabilities are not coming form DB
            // The global $wp_user_roles
            $role->remove_cap($old_slug);
            $role->add_cap($new_slug, $granted);

            $result = $this->_prepare_capability($new_slug, $granted, true);
        } else {
            throw new OutOfRangeException(
                "Capability {$old_slug} does not exist for role {$role_slug}"
            );
        }

        return $result;
    }

    /**
     * Update capability for user
     *
     * @param string $old_slug
     * @param string $new_slug
     * @param mixed  $user_identifier
     *
     * @return int
     *
     * @access private
     * @version 6.9.33
     */
    private function _update_capability_for_user(
        $old_slug, $new_slug, $user_identifier
    ) {
        $user = $this->_get_user_by_identifier($user_identifier);

        if (array_key_exists($old_slug, $user->caps)) {
            // Persist the granted flag
            $granted = $user->caps[$old_slug];

            // Use core functions to add/remove cap to take into consideration
            // setups were roles and capabilities are not coming form DB
            // The global $wp_user_roles
            $user->remove_cap($old_slug);
            $user->add_cap($new_slug, $granted);

            $result = $this->_prepare_capability($new_slug, $granted, true);
        } else {
            throw new OutOfRangeException(
                "Capability {$old_slug} does not exist for user {$user->ID}"
            );
        }

        return $result;
    }

    /**
     * Iterate over all roles and delete existing capability
     *
     * @param string $capability
     *
     * @return int
     *
     * @access private
     * @version 6.9.33
     */
    private function _delete_capability_from_all_roles($capability)
    {
        $updates = 0; // Count number of updates

        // Iterating of the list of all roles and update old cap with new
        foreach(wp_roles()->role_objects as $role) {
            // Increment the number of updates so we know that something was
            // actually changed
            $updates += $this->_delete_capability_from_role(
                $capability, $role->name
            );
        }

        return $updates;
    }

    /**
     * Delete capability from role
     *
     * @param string $capability
     * @param string $role_slug
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.33
     */
    private function _delete_capability_from_role($capability, $role_slug)
    {
        if ($this->exists($capability, 'role', $role_slug)) {
            $role = wp_roles()->role_objects[$role_slug];

            $role->remove_cap($capability);
        }

        return true;
    }

    /**
     * Delete capability for user
     *
     * @param string $capability
     * @param string $new_slug
     * @param mixed  $user_identifier
     *
     * @return int
     *
     * @access private
     * @version 6.9.33
     */
    private function _delete_capability_from_user($capability, $user_identifier)
    {
        if ($this->exists($capability, 'user', $user_identifier)) {
            $user = $this->_get_user_by_identifier($user_identifier);

            $user->remove_cap($capability);
        }

        return true;
    }

    /**
     * Prepare the list of all capabilities registered for roles
     *
     * @return array
     *
     * @access private
     * @version 6.9.33
     */
    private function _prepare_all_role_capabilities()
    {
        $all_caps = [];

        foreach (wp_roles()->role_objects as $role) {
            if (is_array($role->capabilities)) {
                $all_caps = array_merge($all_caps, $role->capabilities);
            }
        }

        return array_unique(array_keys($all_caps));
    }

}