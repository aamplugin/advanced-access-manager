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
     * Resource internal identifier
     *
     * Some resource may have unique identifier like each post or term has unique
     * auto-incremented ID, or post type - unique slug. Other resource, like menu,
     * toolbar, do not have unique.
     *
     * @var mixed
     * @access private
     *
     * @version 7.0.0
     */
    private $_internal_id = null;

    /**
     * WordPress core instance of the resource
     *
     * @var mixed
     * @access private
     *
     * @version 7.0.0
     */
    private $_core_instance = null;

    /**
     * Constructor
     *
     * If $resource_identifier is not provided, the entire resource instance acts as
     * permissions aggregate. An aggregate is like a container of all explicitly
     * defined permissions for a given resource type
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param mixed                               $resource_identifier [Optional]
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($access_level, $resource_identifier = null)
    {
        $this->_access_level = $access_level;

        if (method_exists($this, 'pre_init_hook')) {
            $this->pre_init_hook($resource_identifier);
        } elseif (is_scalar($resource_identifier) || is_array($resource_identifier)) {
            $this->_internal_id = $resource_identifier;
        }

        // Read explicitly defined settings from DB
        $permissions = AAM_Framework_Manager::_()->settings([
            'access_level' => $access_level
        ])->get_setting($this->_get_settings_ns(), []);

        if (!empty($permissions)) {
            $this->_explicit_permissions = $permissions;
        }

        // JSON Access Policy is deeply embedded in the framework, thus take it into
        // consideration during resource initialization
        if (AAM_Framework_Manager::_()->config->get('service.policies.enabled', true)) {
            $permissions = $this->_apply_policy($permissions);
        }

        // Allow other implementations to modify defined settings
        $this->_permissions = apply_filters(
            'aam_framework_resource_init_permissions_filter',
            $permissions,
            $this
        );

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
     * @param array  $arguments
     *
     * @return mixed
     * @access public
     *
     * @since 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (array_key_exists($name, $this->_extended_methods)) {
            $response = call_user_func_array(
                $this->_extended_methods[$name], $arguments
            );
        } elseif (is_object($this->_core_instance)
            && method_exists($this->_core_instance, $name)
        ) {
            $response = call_user_func_array(
                array($this->_core_instance, $name), $arguments
            );
        } else {
            throw new BadMethodCallException(sprintf(
                'Method %s does not exist in %s resource', $name, static::class
            ));
        }

        return $response;
    }

    /**
     * Get a property of a core instance
     *
     * @param string $name
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (is_object($this->_core_instance)) {
            $response = $this->_core_instance->{$name};
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Property does not exist',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function get_internal_id($serialize = true)
    {
        if (is_array($this->_internal_id) && $serialize) {
            $result = implode('|', array_values($this->_internal_id));
        } else {
            $result = $this->_internal_id;
        }

        return $result;
    }

    /**
     * Get WordPress core instance
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get_core_instance()
    {
        return $this->_core_instance;
    }

    /**
     * @inheritDoc
     */
    public function is_customized()
    {
        return !empty($this->_explicit_permissions);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->_explicit_permissions = [];

        return AAM_Framework_Manager::_()->settings([
            'access_level' => $this->get_access_level()
        ])->delete_setting($this->_get_settings_ns());
    }

    /**
     * @inheritDoc
     */
    public function get_permissions($explicit_only = false)
    {
        if ($explicit_only) {
            $result = $this->_explicit_permissions;
        } else {
            $result = $this->_permissions;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set_permissions($permissions, $explicit_only = true)
    {
        $permissions = $this->_sanitize_permissions($permissions);

        if ($explicit_only) {
            // First, settings the explicit permissions
            $this->_explicit_permissions = $permissions;

            // Overriding the final set of settings
            $this->_permissions = array_merge($this->_permissions, $permissions);

            // Store changes in DB
            $result = AAM_Framework_Manager::_()->settings([
                'access_level' => $this->get_access_level()
            ])->set_setting($this->_get_settings_ns(), $permissions);
        } else {
            $this->_permissions = $permissions;
            $result             = true;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function add_permission($permission_key, ...$args)
    {
        $permissions = $this->_explicit_permissions;

        // Determine if $permission argument was provided
        $permission = array_shift($args);
        $permission = !is_null($permission) ? $permission : 'deny';

        $permissions[$permission_key] = apply_filters(
            'aam_framework_resource_add_permission_filter',
            $this->_sanitize_permission($permission, $permission_key),
            $args
        );

        return $this->set_permissions($permissions, true);
    }

    /**
     * @inheritDoc
     */
    public function add_permissions($permissions, ...$args)
    {
        $normalized = [];

        foreach($permissions as $key => $value) {
            if (is_numeric($key) && is_string($value)) {
                $normalized[$value] = apply_filters(
                    'aam_framework_resource_add_permission_filter',
                    $this->_sanitize_permission(true, $value),
                    $args
                );
            } elseif (is_string($key)) {
                $normalized[$key] = apply_filters(
                    'aam_framework_resource_add_permission_filter',
                    $this->_sanitize_permission($value, $key),
                    $args
                );
            }
        }

        return $this->set_permissions($normalized, true);
    }

    /**
     * @inheritDoc
     */
    public function remove_permission($permission_key)
    {
        $result      = true;
        $permissions = $this->_explicit_permissions;

        if (array_key_exists($permission_key, $permissions)) {
            unset($permissions[$permission_key]);

            $result = $this->set_permissions($permissions, true);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function remove_permissions($permission_keys)
    {
        $permissions = array_filter(
            $this->_explicit_permissions,
            function ($p) use ($permission_keys) {
                return !in_array($p, $permission_keys, true);
            }, ARRAY_FILTER_USE_KEY
        );

        return $this->set_permissions($permissions, true);
    }

    /**
     * Check if permission exists
     *
     * @param string $permission_key
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($permission_key)
    {
        return array_key_exists($permission_key, $this->_permissions);
    }

    /**
     * Get specific permission
     *
     * @param string $permission_key
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($permission_key)
    {
        $result = null;

        if ($this->offsetExists($permission_key)) {
            $result = $this->_permissions[$permission_key];
        }

        return $result;
    }

    /**
     * Set specific permission
     *
     * @param string $permission_key
     * @param mixed  $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($permission_key, $permission)
    {
        return $this->add_permission($permission_key, $permission);
    }

    /**
     * Remove specific permission
     *
     * @param string $permission_key
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($permission_key)
    {
        return $this->remove_permission($permission_key);
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
        $id = $this->get_internal_id();

        // Compile the namespace
        if (empty($id)) {
            $result = constant('static::TYPE');
        } else {
            $result = constant('static::TYPE') . '.' . $id;
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
            $response[$key] = $this->_sanitize_permission($permission, $key);
        }

        return $response;
    }

    /**
     * Sanitize permission
     *
     * Take given permission and convert to a standardized permission model
     *
     * @param mixed  $permission
     * @param string $permission_key
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _sanitize_permission($permission, $permission_key)
    {
        if (is_string($permission)) {
            $result = [
                'effect' => strtolower($permission)
            ];
        } elseif (is_bool($permission)) {
            $result = [
                'effect' => $permission ? 'deny' : 'allow'
            ];
        } elseif (is_numeric($permission)) {
            $result = [
                'effect' => intval($permission) > 0 ? 'deny' : 'allow'
            ];
        } elseif (is_array($permission)) {
            $result = array_merge([ 'effect' => 'deny' ], $permission);
        } else {
            $result = [ 'effect' => 'deny' ];
        }

        return $this->_normalize_permission($result, $permission_key);
    }

    /**
     * Allow individual resources to normalize permission model further
     *
     * @param array  $permission
     * @param string $permission_key
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_permission($permission, $permission_key)
    {
        return $permission;
    }

    /**
     * Apply permissions extracted from policies
     *
     * @param array $permissions
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _apply_policy($permissions)
    {
        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}