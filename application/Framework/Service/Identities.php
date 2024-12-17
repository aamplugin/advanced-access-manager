<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Users & Roles (aka Identity) Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Identities
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Permissions map between user and role resources
     *
     * @version 7.0.0
     */
    const PERMISSION_MAP = [
        'list_user'            => 'list_users',
        'edit_user'            => 'edit_users',
        'delete_user'          => 'delete_users',
        'promote_user'         => 'promote_users',
        'change_user_password' => 'change_users_password'
    ];

    /**
     * Get list of users
     *
     * @param array  $args        [Optional]
     * @param string $result_type [Optional]
     *
     * @return array|Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function get_users($args = [], $result_type = 'list')
    {
        try {
            $result    = [];
            $user_data = $this->users->list($args, $result_type);

            if ($result_type !== 'summary') {
                // Prepare the generator
                $generator = function () use ($user_data) {
                    foreach ($user_data['list'] as $user) {
                        yield $this->_get_resource(
                            AAM_Framework_Type_Resource::USER, $user
                        );
                    }
                };

                $result['list'] = $generator();
            }

            if (in_array($result_type, [ 'full', 'summary' ], true)) {
                $result['summary'] = $user_data['summary'];
            }
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_users method
     *
     * @param array $args [Optional]
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function users($args = [])
    {
        return $this->get_users($args);
    }

    /**
     * Get list of roles
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function get_roles()
    {
        try {
            $generator = function() {
                foreach($this->roles->get_editable_roles() as $role) {
                    yield $this->_get_resource(
                        AAM_Framework_Type_Resource::ROLE, $role
                    );
                }
            };

            $result = $generator();
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_roles method
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function roles()
    {
        return $this->get_roles();
    }

    /**
     * Get user resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_User
     *
     * @access public
     * @version 7.0.0
     */
    public function get_user($identifier)
    {
        try {
            $result = $this->_get_resource(
                AAM_Framework_Type_Resource::USER, $identifier
            );
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_user method
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_User
     *
     * @access public
     * @version 7.0.0
     */
    public function user($identifier)
    {
        return $this->get_user($identifier);
    }

    /**
     * Get role resource
     *
     * @param string $role_slug
     *
     * @return AAM_Framework_Resource_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function get_role($role_slug)
    {
        try {
            $result = $this->_get_resource(
                AAM_Framework_Type_Resource::ROLE, $role_slug
            );
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_role method
     *
     * @param string $role_slug
     *
     * @return AAM_Framework_Resource_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function role($role_slug)
    {
        return $this->get_role($role_slug);
    }

    /**
     * Reset either all identity rules or for a specific identity type
     *
     * @param string $identity_type [Optional] Allowed either "user" or "role"
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($identity_type = null, $identity_id = null)
    {
        try {
            if (!empty($identity_type) && !in_array($identity_type, [
                AAM_Framework_Type_Resource::USER,
                AAM_Framework_Type_Resource::ROLE
            ], true)) {
                throw new InvalidArgumentException('Invalid identity type');
            }

            // If identifier is not provided, assume that we are trying either to
            // reset permissions for specific identity type of all permissions
            if (empty($identity_id)) {
                $service = $this->settings($this->_get_access_level());

                if (empty($identity_type)) {
                    // Resetting both resources
                    $result = $service->delete_setting(
                        AAM_Framework_Type_Resource::USER
                    );

                    $result = $result && $service->delete_setting(
                        AAM_Framework_Type_Resource::ROLE
                    );
                } else {
                    $result = $service->delete_setting($identity_type);
                }
            } else {
                $identity = $this->_get_access_level()->get_resource(
                    $identity_type, $identity_id
                );

                $result = $identity->reset();
            }
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if current access level is restricted for given permission
     *
     * @param mixed  $identity
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied_to($identity, $permission)
    {
        try {
            // If $identity is the user or role proxy, take into considerations all
            // the necessary relationships and determine if permission is denied or
            // not
            if (is_a($identity, AAM_Framework_Proxy_Interface::class)) {
                $result = $this->_determine_instance_access($identity, $permission);
            } elseif (is_a($identity, AAM_Framework_Resource_Interface::class)) {
                $result = $this->_determine_resource_access($identity, $permission);
            } else {
                throw new InvalidArgumentException('Invalid identity provided');
            }
        }  catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if current access level has given permission
     *
     * @param mixed  $identity
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed_to($identity, $permission)
    {
        $result = $this->is_denied_to($identity, $permission);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get identity resource
     *
     * @param string $type
     * @param mixed  $identifier
     *
     * @return AAM_Framework_Resource_Role|AAM_Framework_Resource_User
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($type, $identifier)
    {
        return $this->_get_access_level()->get_resource($type, $identifier);
    }

    /**
     * Determine if current access level has denied permission for given role or
     * user
     *
     * This method also takes into consideration inheritance mechanism and properly
     * merged access controls to determine the correct permissions
     *
     * @param AAM_Framework_Proxy_User|AAM_Framework_Proxy_Role $identity
     * @param string                                            $permission
     *
     * @return bool|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _determine_instance_access($identity, $permission)
    {
        if (is_a($identity, AAM_Framework_Proxy_User::class)) {
            // Step #1. Checking if there are any explicit settings defined for a
            // given user and if so use only them
            $result = $this->user($identity)->is_denied_to($permission);

            // Iterate over the list of all user roles and merge access controls
            // accordingly
            if (is_null($result)) {
                $multi_support = $this->config->get(
                    'core.settings.multi_access_levels'
                );

                if ($multi_support) {
                    $roles = $identity->roles;
                } else {
                    $user_roles = array_values($identity->roles);
                    $roles = !empty($user_roles) ? [ array_shift($user_roles) ] : [];
                }

                $permissions = [];

                foreach($roles as $role) {
                    $permissions = $this->misc->merge_permissions(
                        $permissions,
                        $this->role($role)->get_permissions(),
                        AAM_Framework_Type_Resource::ROLE
                    );
                }

                $role_permission = self::PERMISSION_MAP[$permission];

                if (array_key_exists($role_permission, $permissions)) {
                    $result = $permissions[$role_permission]['effect'] !== 'allow';
                }
            }
        } elseif (is_a($identity, AAM_Framework_Proxy_Role::class)) {
            $result = $this->role($identity->slug)->is_denied_to($permission);
        } else {
            throw new InvalidArgumentException('Invalid proxy instance');
        }

        return $result;
    }

    /**
     * Determine if current access level has denied permission for a given resource
     *
     * @param AAM_Framework_Resource_User|AAM_Framework_Resource_Role $resource
     * @param string                                                  $permission
     *
     * @return bool|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _determine_resource_access($resource, $permission)
    {
        $result = null;

        if (is_object($resource) && in_array(get_class($resource), [
                AAM_Framework_Resource_User::class,
                AAM_Framework_Resource_Role::class
            ], true)
        ) {
            $result = $resource->is_denied_to($permission);
        } else {
            throw new InvalidArgumentException('Invalid identity provided');
        }

        return $result;
    }

}