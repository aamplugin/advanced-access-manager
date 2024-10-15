<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Users & Roles (aka Identity) Governance
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Identity
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Identity types
     *
     * @version 7.0.0
     */
    const IDENTITY_TYPE = array(
        'role',
        'user_role',
        'role_level',
        'user',
        'user_level'
    );

    /**
     * Allowed effect types
     *
     * @version 7.0.0
     */
    const EFFECT_TYPES = array(
        'allow',
        'deny'
    );

    /**
     * Allowed permission types
     *
     * @version 7.0.0
     */
    const PERMISSION_TYPES = array(
        'list_role',
        'list_user',
        'edit_user',
        'delete_user',
        'change_user_password',
        'change_user_role'
    );

    /**
     * Return list of permissions for give subject
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions($inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->get_resource(true, $inline_context);

            foreach($resource->get_permissions() as $id => $permission) {
                array_push($result, $this->_prepare_permission(
                    $permission, $id, $resource
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing permission by ID
     *
     * @param string     $id             Permission ID
     * @param array|null $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permission_by_id($id, $inline_context = null)
    {
        try {
            $resource    = $this->get_resource(true, $inline_context);
            $permissions = $resource->get_permissions();

            if (!array_key_exists($id, $permissions)) {
                throw new OutOfRangeException('Permission does not exist');
            }

            $result = $this->_prepare_permission(
                $permissions[$id], $id, $resource
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Create new permission
     *
     * @param array $permission     Permission data
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function create_permission(array $permission, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $this->_validate_permission($permission);

            $resource = $this->get_resource(false, $inline_context);

            // Creating a permission key
            $key = uniqid('id_');

            if (!$resource->set_permission($key, $permission)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_permission_by_id($key);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing permission
     *
     * @param string     $id             Permission ID
     * @param string     $effect         Either allow or deny
     * @param array|null $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_permission($id, $effect, $inline_context = null)
    {
        try {
            $resource   = $this->get_resource(false, $inline_context);
            $permission = $this->get_permission_by_id($id, $inline_context);

            // Update permission's effect
            if (in_array($effect, self::EFFECT_TYPES, true)) {
                $permission['effect'] = $effect;
            } else {
                throw new InvalidArgumentException('The effect is invalid');
            }

            // Note! Getting here all rules (even inherited) to ensure that user can
            // override the inherited rule
            $permissions = $resource->get_permissions();

            // Override the permission or create new one
            $permissions[$id] = $permission;
            $success          = $resource->set_permissions($permissions);

            if (empty($success)) {
                throw new RuntimeException('Failed to update permissions');
            }

            $result = $this->get_permission_by_id($id, $inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete permission
     *
     * @param string     $id             Permission ID
     * @param array|null $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_permission($id, $inline_context = null)
    {
        try {
            $resource = $this->get_resource(false, $inline_context);

            // Note! User can delete only explicitly set rule (overwritten rule)
            $permissions = $resource->get_permissions(true);

            if (!array_key_exists($id, $permissions)) {
                throw new OutOfRangeException(
                    'Permission is not explicitly defined'
                );
            } else {
                unset($permissions[$id]);
            }

            $result = $resource->set_permissions($permissions);

            if (!$result) {
                throw new RuntimeException('Failed to persist changes');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all permissions
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($inline_context = null)
    {
        try {
            // Reset settings to default
            $success = $this->get_resource()->reset();

            if ($success) {
                $result = $this->get_permissions($inline_context);
            } else {
                throw new RuntimeException('Failed to reset permissions');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Determine if specific action is allowed to given identity
     *
     * @param string     $identity_type  Can be one of the self::IDENTITY_TYPE
     * @param string|int $identity       Identity ID - role slug, user ID or level ID
     * @param string     $permission     Ca be one of the self::PERMISSION_TYPES
     * @param array|null $inline_context Inline context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to(
        $identity_type, $identity, $permission, $inline_context = null
    ) {
        try {
            if ($identity_type === 'role') {
                $result = $this->_is_role_allowed_to(
                    $identity, $permission, $inline_context
                );
            } elseif ($identity_type === 'user_role') {
                $result = $this->get_resource(false, $inline_context)->is_allowed_to(
                    'user_role', $identity, $permission
                );
            } elseif ($identity_type === 'role_level') {
                $result = $this->get_resource(false, $inline_context)->is_allowed_to(
                    'role_level', $identity, $permission
                );
            } elseif ($identity_type === 'user') {
                $result = $this->_is_user_allowed_to(
                    $identity, $permission, $inline_context
                );
            } elseif ($identity_type === 'user_level') {
                $result = $this->get_resource(false, $inline_context)->is_allowed_to(
                    'user_level', $identity, $permission
                );
            } else {
                throw new InvalidArgumentException('The identity type is incorrect');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Determine if specific action is denied to given identity
     *
     * @param string     $identity_type  Can be one of the self::IDENTITY_TYPE
     * @param string|int $identity       Identity ID - role slug, user ID or level ID
     * @param string     $permission     Ca be one of the self::PERMISSION_TYPES
     * @param array|null $inline_context Inline context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied_to(
        $identity_type, $identity, $permission, $inline_context = null
    ) {
        $result = $this->is_allowed_to(
            $identity_type, $identity, $permission, $inline_context
        );

        return is_bool($result) ? !$result : null;
    }

    /**
     * Based on existing access controls return WP_User_Query filters
     *
     * @param array|null $inline_context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_user_query_filters($inline_context = null)
    {
        global $wpdb;

        $result = [];

        try {
            $resource    = $this->get_resource();
            $permissions = $resource->get_permissions();

            // Making sure that query var properties are properly initialized
            $role__not_in  = [];
            $login__not_in = [];
            $target_levels = [];

            foreach($permissions as $permission) {
                if ($permission['permission'] === 'list_user'
                    && $permission['effect'] === 'deny'
                ) {
                    if ($permission['identity_type'] === 'user_role') {
                        array_push($role__not_in, $permission['identity']);
                    } elseif ($permission['identity_type'] === 'user') {
                        // Get user by ID
                        $user = get_user_by('ID', $permission['identity']);

                        if (is_a($user, WP_User::class)) {
                            array_push($login__not_in, $user->user_login);
                        }
                    } elseif ($permission['identity_type'] === 'user_level') {
                        array_push($target_levels, intval($permission['identity']));
                    }
                }
            }

            if (count($role__not_in)) {
                $result['role__not_in'] = $role__not_in;
            }

            if (count($login__not_in)) {
                $result['login__not_in'] = $login__not_in;
            }

            if (count($target_levels) > 0) {
                $result['meta_key']     = $wpdb->get_blog_prefix() . 'user_level';
                $result['meta_value']   = $target_levels;
                $result['meta_compare'] = 'NOT IN';
                $result['meta_type']    = 'NUMERIC';
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get identity resource
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_Identity
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($reload = false, $inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::IDENTITY, null, $reload
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if permission is allowed for given role
     *
     * @param string $role_slug
     * @param string $permission
     * @param array  $inline_context
     *
     * @return boolean|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_role_allowed_to($role_slug, $permission, $inline_context)
    {
        $resource = $this->get_resource(false, $inline_context);

        // Step #1. Determine if access controls for the role are explicitly defined
        //          and if so, use it
        $result = $resource->is_allowed_to('role', $role_slug, $permission);

        // Step #2. If the result is null, then it means that also would like to
        //          check role level
        if (is_null($result)) {
            $max_user_level = AAM_Framework_Utility_Misc::get_max_user_level(
                AAM_Framework_Manager::roles()->get_role($role_slug)->capabilities
            );

            $result = $resource->is_allowed_to(
                'role_level', $max_user_level, $permission
            );
        }

        return $result;
    }

    /**
     * Determine if current user has permission over provided user
     *
     * @param int        $user_id
     * @param string     $permission
     * @param array|null $inline_context
     *
     * @return boolean|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_user_allowed_to($user_id, $permission, $inline_context)
    {
        $resource = $this->get_resource(false, $inline_context);

        // Check #1. Do we have access controls defined for a give user explicitly?
        $result = $resource->is_allowed_to('user', $user_id, $permission);

        if (is_null($result)) {
            $user = get_user_by('ID', $user_id);

            // Making sure that user actually exists
            if (is_a($user, WP_User::class)) {
                // Check #2. Do we have access controls defined for user's access
                //           level?
                $result = $resource->is_allowed_to(
                    'user_level', $user->user_level, $permission
                );

                // Check #3. Finally, iterate over the list of user's roles and
                //           determine if access
                if (is_null($result) && !empty($user->roles)) {
                    // Determine list of roles to check
                    $multi_support = AAM::api()->configs()->get_config(
                        'core.settings.multi_access_levels'
                    );

                    if ($multi_support) {
                        $configs = AAM::api()->configs();

                        // If preference is not explicitly defined, fetch it from the
                        // AAM configs
                        $preference = $configs->get_config(
                            'core.settings.' . AAM_Framework_Type_Resource::IDENTITY . '.merge.preference',
                            $configs->get_config('core.settings.merge.preference')
                        );

                        foreach($user->roles as $role) {
                            $res = $resource->is_allowed_to(
                                'user_role', $role, $permission
                            );

                            if ($res === false) { // Denied
                                if ($preference === 'deny') {
                                    $result = false;
                                    break; // Break immediately
                                } else {
                                    $result = $result || $res;
                                }
                            } elseif ($res !== null) {
                                $result = $res;
                            }
                        }
                    } else {
                        $result = $resource->is_allowed_to(
                            'user_role', array_values($user->roles)[0], $permission
                        );
                    }
                }
            } else {
                throw new OutOfBoundsException(
                    sprintf('User with ID %d does not exist', $user_id)
                );
            }
        }

        return $result;
    }

    /**
     * Prepare permission model
     *
     * @param array                           $permissions
     * @param string                          $id
     * @param AAM_Framework_Resource_Identity $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_permission($permission, $id, $resource)
    {
        $explicit = $resource->get_permissions(true);

        return array_merge([
            'id'           => $id,
            'is_inherited' => !array_key_exists($id, $explicit),
        ], $permission);
    }

    /**
     * Validate the permission's incoming data
     *
     * @param array $permission Incoming permission's data
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_permission(array $permission)
    {
        if (isset($permission['identity_type'])) {
            $identity_type = $permission['identity_type'];
        } else {
            $identity_type = null;
        }

        if (!in_array($identity_type, self::IDENTITY_TYPE, true)) {
            throw new InvalidArgumentException('Invalid identity type');
        }

        // Do some additional validate
        if ($identity_type === 'user') {
            $this->_validate_user($permission['identity']);
        } elseif (in_array($identity_type, ['role', 'user_role'], true)) {
            $this->_validate_role($permission['identity']);
        } elseif (in_array($identity_type, ['role_level', 'user_level'], true)) {
            $this->_validate_level($permission['identity']);
        }
    }

    /**
     * Validate user identifier
     *
     * @param string|int $id
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_user($id)
    {
        $user = null;

        if (is_numeric($id)) { // Get user by ID
            $user = get_user_by('id', $id);
        } elseif (is_string($id)) {
            if (strpos($id, '@') > 0) { // Email?
                $user = get_user_by('email', $id);
            } elseif (strlen(sanitize_user($id))) {
                $user = get_user_by('login', $id);
            }
        }

        if (!is_a($user, 'WP_User')
            && !apply_filters('aam_validate_user_identifier_filter', true, $id)
        ) {
            throw new OutOfRangeException(
                'Invalid user identifier or user does not exist'
            );
        }
    }

    /**
     * Validate role
     *
     * @param string $slug
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_role($slug)
    {
        $roles = AAM_Framework_Manager::roles();

        if (!$roles->is_role($slug)
            && !apply_filters('aam_validate_role_identifier_filter', true, $slug)
        ) {
            throw new OutOfRangeException(
                'Invalid role identifier or role does not exist'
            );
        }
    }

    /**
     * Validate level
     *
     * @param string|int $level
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_level($level)
    {
        if (!is_numeric($level)) {
            throw new InvalidArgumentException('Invalid user level identifier');
        }
    }

}