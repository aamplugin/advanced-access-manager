<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait to represents AAM resource concept
 *
 * AAM Resource is a website resource that you manage access to for users, roles or
 * visitors. For example, it can be any website post, page, term, backend menu etc.
 *
 * On another hand, AAM Resource is a “container” with specific settings for any user,
 * role or visitor. For example login, logout redirect, default category or access
 * denied redirect rules.
 *
 * @method AAM_Framework_Service_Urls urls(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_ApiRoutes api_routes(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Jwts jwts(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_LoginRedirect login_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_BackendMenu backend_menu(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Metaboxes metaboxes(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Widgets widgets(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Roles roles(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Users users(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Posts posts(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Terms terms(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_PostTypes post_types(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Taxonomies taxonomies(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Capabilities caps(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Settings settings(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Policies policies(mixed $access_level = null, array $settings = [])
 * @method AAM_Framework_Service_Hooks hooks(mixed $access_level = null, array $settings = [])
 *
 * @property AAM_Framework_Utility_Cache $cache
 * @property AAM_Framework_Utility_ObjectCache $object_cache
 * @property AAM_Framework_Utility_Capabilities $caps
 * @property AAM_Framework_Utility_Capabilities $capabilities
 * @property AAM_Framework_Utility_Config $config
 * @property AAM_Framework_Utility_Misc $misc
 * @property AAM_Framework_Utility_Redirect $redirect
 * @property AAM_Framework_Utility_Roles $roles
 * @property AAM_Framework_Utility_Users $users
 * @property AAM_Framework_Utility_Db $db
 * @property AAM_Framework_Utility_AccessLevels $access_levels
 * @property AAM_Framework_Utility_Jwt $jwt
 * @property AAM_Framework_Utility_Policy $policy
 * @property AAM_Framework_Utility_Content $content
 * @property AAM_Framework_Utility_Rest $rest
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Resource_BaseTrait
{

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Interface
     * @access private
     *
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Collection of extended methods
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_extended_methods = [];

    /**
     * Resource permissions
     *
     * Array of final permissions. The final permissions are those that have been
     * properly inherited and merged.
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_permissions = [];

    /**
     * Array of flags to indicate which resource already triggered inheritance
     * mechanism
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_inheritance_completed = [];

    /**
     * Explicit permissions (not inherited from parent access level)
     *
     * When resource is initialized, it already contains the final set of the permissions,
     * inherited from the parent access levels. This property contains permissions
     * that are explicitly defined for current resource.
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_explicit_permissions = [];

    /**
     * Constructor
     *
     * Initialize the resource container
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($access_level)
    {
        $this->_access_level = $access_level;

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_framework_resource_methods_filter',
            $this->_extended_methods,
            $this
        );

        if (is_array($closures)) {
            foreach($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }

        // Initialize permissions
        $this->_init_permissions();
    }

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * Proxy methods to WordPress core instance
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     * @access public
     *
     * @since 7.0.0
     */
    public function __call($name, $args)
    {
        $response = null;

        if (array_key_exists($name, $this->_extended_methods)) {
            $response = call_user_func_array(
                $this->_extended_methods[$name], $args
            );
        } elseif (AAM_Framework_Manager::_()->has_service($name)) {
            $response = AAM_Framework_Manager::_()->{$name}(
                $this->get_access_level(), ...$args
            );
        } else {
            throw new BadMethodCallException(sprintf(
                'Method %s does not exist in %s resource',
                esc_js($name),
                static::class
            ));
        }

        return $response;
    }

    /**
     * Property overload
     *
     * @param string $name
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        $result = null;

        if ($name === 'type') {
            $result = $this->type;
        } elseif (AAM_Framework_Manager::_()->has_utility($name)) {
            $result = AAM_Framework_Manager::_()->{$name};
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Property does not exist',
                AAM_VERSION
            );
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function is_customized($resource_identifier = null)
    {
        if (!empty($resource_identifier)) {
            $id     = $this->_get_resource_id($resource_identifier);
            $result = !empty($this->_explicit_permissions[$id]);
        } else {
            $result = !empty($this->_explicit_permissions);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function reset($resource_identifier = null)
    {
        if (!empty($resource_identifier)) {
            $id = $this->_get_resource_id($resource_identifier);

            if (array_key_exists($id, $this->_explicit_permissions)) {
                unset($this->_explicit_permissions[$id]);
            }

            if (array_key_exists($id, $this->_permissions)) {
                unset($this->_permissions[$id]);
            }

            if (isset($this->_inheritance_completed[$id])) {
                unset($this->_inheritance_completed[$id]);
            }

            $result = $this->settings()->set_setting(
                $this->_get_settings_ns(),
                $this->_remove_sys_attributes($this->_explicit_permissions)
            );
        } else {
            $this->_explicit_permissions  = [];
            $this->_permissions           = [];
            $this->_inheritance_completed = [];

            $result = $this->settings()->delete_setting($this->_get_settings_ns());
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get_permissions($resource_identifier = null)
    {
        if (empty($resource_identifier)) {
            $result = $this->_permissions;
        } else {
            $result = $this->_get_permissions($resource_identifier);
        }

        // Remove empty permission containers
        return array_filter(
            $this->_remove_sys_attributes($result),
            function($perms) { return !empty($perms); }
        );
    }

    /**
     * @inheritDoc
     */
    public function set_permissions(array $permissions, $resource_identifier = null)
    {
        if (!empty($resource_identifier)) {
            $id = $this->_get_resource_id($resource_identifier);
        }

        // First, settings the explicit permissions
        if (empty($id)) {
            $this->_explicit_permissions = $permissions;
            $this->_permissions          = array_replace(
                $this->_permissions,
                $permissions
            );
        } else {
            $this->_explicit_permissions[$id] = $permissions;
            $this->_permissions[$id]          = array_replace(
                !empty($this->_permissions[$id]) ? $this->_permissions[$id]: [],
                $permissions
            );
        }

        // Store changes in DB
        $result = $this->settings()->set_setting(
            $this->_get_settings_ns(),
            $this->_remove_sys_attributes($this->_explicit_permissions)
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set_permission(
        $resource_identifier,
        $permission_key,
        $permission,
        ...$args
    ) {
        $id = $this->_get_resource_id($resource_identifier);

        // Prepare the permission that will be merged with others
        $sanitized = apply_filters(
            'aam_resource_set_permission_filter',
            $this->_sanitize_permission($permission),
            $args
        );

        // Update explicit permissions
        if (!array_key_exists($id, $this->_explicit_permissions)) {
            $this->_explicit_permissions[$id] = [];
        }

        $this->_explicit_permissions[$id] = array_replace(
            $this->_explicit_permissions[$id],
            [ $permission_key => $sanitized ]
        );

        // Store changes in DB
        $result = $this->settings()->set_setting(
            $this->_get_settings_ns(),
            $this->_remove_sys_attributes($this->_explicit_permissions)
        );

        // Also sync it with final set of permissions
        if (!array_key_exists($id, $this->_permissions)) {
            $this->_permissions[$id] = [];
        }

        $this->_permissions[$id] = array_replace(
            $this->_permissions[$id],
            [ $permission_key => $sanitized ]
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get_permission($resource_identifier, $permission_key)
    {
        $result      = null;
        $permissions = $this->_get_permissions($resource_identifier);

        if (!empty($permissions[$permission_key])) {
            $result = $permissions[$permission_key];
        }

        return is_array($result) ? $this->_remove_sys_attributes($result) : null;
    }

    /**
     * @inheritDoc
     */
    public function remove_permission(
        $resource_identifier,
        $permission_key
    ) {
        $result = true;
        $id     = $this->_get_resource_id($resource_identifier);

        // If permission is part of explicit, delete it from their and store changes
        if (!empty($this->_explicit_permissions[$id][$permission_key])) {
            unset($this->_explicit_permissions[$id][$permission_key]);

            // Allow to re-init the permissions
            if (isset($this->_inheritance_completed[$id])) {
                unset($this->_inheritance_completed[$id]);
            }

            // Store changes in DB
            $result = $this->settings()->set_setting(
                $this->_get_settings_ns(),
                $this->_remove_sys_attributes($this->_explicit_permissions)
            );
        }

        if (!empty($this->_permissions[$id][$permission_key])) {
            unset($this->_permissions[$id][$permission_key]);
        }

        return $result;
    }

    /**
     * Initialize basic set of permissions
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _init_permissions()
    {
        // Read explicitly defined settings from DB
        $permissions = $this->settings()->get_setting($this->_get_settings_ns(), []);

        if (!is_array($permissions)) { // Deal with corrupted data
            $permissions = [];
        } else {
            $permissions = $this->_add_sys_attributes($permissions);
        }

        // Store explicit permissions separately from final set of permissions
        $this->_explicit_permissions = $permissions;

        // These are the base permissions that are subject to override
        $this->_permissions = $permissions;

        // JSON Access Policy is deeply embedded in the framework, thus take it into
        // consideration during resource initialization
        if ($this->_should_apply_policies()) {
            $policy_permissions = $this->_add_sys_attributes($this->_apply_policy());

            foreach ($policy_permissions as $resource_id => $permissions) {
                if (array_key_exists($resource_id, $this->_permissions)) {
                    $this->_permissions[$resource_id] = array_replace(
                        $permissions,
                        $this->_permissions[$resource_id]
                    );
                } else {
                    $this->_permissions[$resource_id] = $permissions;
                }
            }
        }

        // Pre-load all explicitly defined permissions
        $inherited_permissions = $this->_add_sys_attributes(
            $this->_trigger_inheritance(),
            [ '__inherited' => true ]
        );

        foreach ($inherited_permissions as $resource_id => $permissions) {
            if (array_key_exists($resource_id, $this->_permissions)) {
                $this->_permissions[$resource_id] = array_replace(
                    $permissions,
                    $this->_permissions[$resource_id]
                );
            } else {
                $this->_permissions[$resource_id] = $permissions;
            }
        }
    }

    /**
     * Determine if we should tap into JSON policies
     *
     * This method performs two crucial checks:
     *  1. Verifies that the JSON Access Policies service is enabled;
     *  2. Confirms that only the lowest possible access level is currently used
     *
     * The second step is extremely important to ensure that we retain proper level
     * flexibility. For instance if policy X is attached to the Editor role, we
     * should be able to detach it from any individual user with Editor role. Without
     * this trade-off users will inherit initialized resourced from Editor role and
     * there will be no chance to suppress them on user level as we have no awareness
     * that these resources where initialized through policies.
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _should_apply_policies()
    {
        $is_enabled      = $this->config->get('service.policies.enabled', true);
        $is_lowest_level = in_array($this->get_access_level()->type, [
            AAM_Framework_Type_AccessLevel::USER,
            AAM_Framework_Type_AccessLevel::VISITOR
        ], true);

        return $is_enabled && $is_lowest_level;
    }

    /**
     * Get settings namespace
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_settings_ns()
    {
        return $this->type;
    }

    /**
     * Get final set of permissions for given resource
     *
     * @param mixed $resource_identifier
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_permissions($resource_identifier)
    {
        // This is done for performance reasons so we do not have to normalize
        // resource twice
        $resource_id = $this->_get_resource_id($resource_identifier);

        if (empty($this->_inheritance_completed[$resource_id])) {
            // Get the base set of permissions
            if (array_key_exists($resource_id, $this->_permissions)) {
                $result = $this->_permissions[$resource_id];
            } else {
                $result = [];
            }

            // Trigger inheritance mechanism
            $this->_permissions[$resource_id] = array_replace(
                $this->_trigger_inheritance($resource_identifier),
                $result
            );

            // Making sure we do not trigger inheritance again
            $this->_inheritance_completed[$resource_id] = true;
        }

        return $this->_permissions[$resource_id];
    }

    /**
     * Trigger inheritance mechanism
     *
     * @param mixed $resource_identifier
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _trigger_inheritance($resource_identifier = null)
    {
        // Allow other implementations to influence set of permissions
        $result = apply_filters(
            'aam_resource_get_permissions_filter',
            [],
            $resource_identifier,
            $this
        );

        // Trigger inheritance mechanism
        return array_replace(
            $this->_inherit_from_parent($resource_identifier),
            $result
        );
    }

    /**
     * Inherit settings from parent access level (if any)
     *
     * @param mixed $resource_identifier [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _inherit_from_parent($resource_identifier = null)
    {
        $parent = $this->get_access_level()->get_parent();
        $result = [];

        if (is_a($parent, AAM_Framework_AccessLevel_Interface::class)) {
            // Merge access settings if multi access levels config is enabled
            $multi_support = $this->config->get('core.settings.multi_access_levels');

            if ($multi_support && $parent->has_siblings()) {
                $siblings = $parent->get_siblings();
            } else {
                $siblings = [];
            }

            // Getting resource from the parent access level
            $result = $parent->get_resource(
                $this->type
            )->get_permissions($resource_identifier);

            foreach ($siblings as $sibling) {
                $sib_perms = $sibling->get_resource(
                    $this->type
                )->get_permissions($resource_identifier);

                if (empty($resource_identifier)) { // Aggregated merge
                    $resource_ids = array_unique(array_merge(
                        array_keys($sib_perms),
                        array_keys($result)
                    ));

                    foreach($resource_ids as $id) {
                        $result[$id] = $this->_add_acl_attributes(
                            $this->misc->merge_permissions(
                                isset($sib_perms[$id]) ? $sib_perms[$id] : [],
                                isset($result[$id]) ? $result[$id] : [],
                                $this->type
                            ),
                            $parent
                        );
                    }
                } else {
                    $result = $this->_add_acl_attributes(
                        $this->misc->merge_permissions(
                            $sib_perms,
                            $result,
                            $this->type
                        ),
                        $parent
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Sanitize array of permissions
     *
     * @param array $permissions
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _sanitize_permissions(array $permissions)
    {
        $response = [];

        foreach($permissions as $key => $permission) {
            $response[$key] = $this->_sanitize_permission($key, $permission);
        }

        return $response;
    }

    /**
     * Sanitize permission
     *
     * Take given permission and convert to a standardized permission model
     *
     * @param mixed $permission
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _sanitize_permission($permission)
    {
        if (is_string($permission)) { // Word like "allow" or "deny"
            $result = [
                'effect' => strtolower($permission)
            ];
        } elseif (is_bool($permission)) { // Boolean "true" or "false"
            $result = [
                'effect' => $permission ? 'deny' : 'allow'
            ];
        } elseif (is_numeric($permission)) { // Numeric "1" or "0"
            $result = [
                'effect' => intval($permission) > 0 ? 'deny' : 'allow'
            ];
        } elseif (is_array($permission)) { // Raw permission data
            $result = array_merge([ 'effect' => 'deny' ], $permission);
        } else {
            $result = [ 'effect' => 'deny' ];
        }

        return $result;
    }

    /**
     * Convert resource identifier into internal ID
     *
     * The internal ID represents unique resource identify AAM Framework users to
     * distinguish between collection of resources
     *
     * @param mixed $identifier
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    private function _get_resource_id($identifier)
    {
        return (string) $identifier;
    }

    /**
     * Apply permissions extracted from policies
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _apply_policy()
    {
        return apply_filters('aam_apply_policy_filter', [], $this);
    }

    /**
     * Add some system attributes to each permission
     *
     * @param array $data
     * @param array $additional [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _add_sys_attributes($data, $additional = [])
    {
        foreach($data as $resource_id => $permissions) {
            $data[$resource_id] = $this->_add_acl_attributes(
                $permissions,
                $this->get_access_level(),
                $additional
            );
        }

        return $data;
    }

    /**
     * Add system attributes to collection of resource permissions
     *
     * @param array $data
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _remove_sys_attributes($data)
    {
        $result = [];

        foreach($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->_remove_sys_attributes($value);
            } elseif (strpos($key, '__') !== 0) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Add access level attributes to a specific resource permissions
     *
     * @param array                               $permissions
     * @param AAM_Framework_AccessLevel_Interface $acl
     * @param array                               $additional  [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _add_acl_attributes($permissions, $acl, $additional = [])
    {
        foreach($permissions as $key => $permission) {
            if (!isset($permission['__access_level'])) {
                $permission['__access_level'] = $acl->type;

                $acl_id = $acl->get_id();

                if (!empty($acl_id)) {
                    $permission['__access_level_id'] = $acl_id;
                }
            }

            $permissions[$key] = array_merge($permission, $additional);
        }

        return $permissions;
    }

}