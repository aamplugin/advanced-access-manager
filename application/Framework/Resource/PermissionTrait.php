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
trait AAM_Framework_Resource_PermissionTrait
{

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Collection of extended methods
     *
     * @var array
     *
     * @access private
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
     *
     * @access private
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
     *
     * @access private
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
     * @var int|string|array|null
     *
     * @access private
     * @version 7.0.0
     */
    private $_internal_id = null;

    /**
     * WordPress core instance of the resource
     *
     * @var mixed
     *
     * @access private
     * @version 7.0.0
     */
    private $_core_instance = null;

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param mixed                               $internal_id
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $internal_id
    ) {
        $this->_access_level = $access_level;
        $this->_internal_id  = $internal_id;

        if (method_exists($this, 'initialize_hook')) {
            $this->initialize_hook();
        };

        // Initialize resource settings & extend with additional methods
        $this->initialize_resource($access_level);
    }

    /**
     * Initialize the resource settings and extend it with additional methods
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_resource(
        AAM_Framework_AccessLevel_Interface $access_level
    ) {
        // Read explicitly defined settings from DB
        $settings = AAM::api()->settings([
            'access_level' => $access_level
        ])->get_setting($this->_get_settings_ns(), []);

        if (!empty($settings)) {
            $this->_explicit_permissions = $settings;
        }

        // Allow other implementations to modify defined settings
        $this->_permissions = apply_filters(
            'aam_initialize_resource_settings_filter',
            $settings,
            $this
        );

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_framework_resource_methods_filter', [], $this
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
     *
     * @access public
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
     *
     * @access public
     * @since 7.0.0
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (array_key_exists($name, $this->_extended_methods)) {
            $response = call_user_func_array(
                $this->_extended_methods[$name], $arguments
            );
        } elseif (is_object($this->_core_instance)) {
            $response = call_user_func_array(
                array($this->_core_instance, $name), $arguments
            );
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Method does not exist',
                AAM_VERSION
            );
        }

        return $response;
    }

    /**
     * Get a property of a core instance
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if (is_object($this->_core_instance)
            && property_exists($this->_core_instance, $name)
        ) {
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
     *
     * @access public
     * @version 7.0.0
     */
    public function get_core_instance()
    {
        return $this->_core_instance;
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
            $result = AAM::api()->settings([
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

        return AAM::api()->settings([
            'access_level' => $this->get_access_level()
        ])->delete_setting($this->_get_settings_ns());
    }

    /**
     * Get settings namespace
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_settings_ns()
    {
        // Compile the namespace
        return constant('static::TYPE');
    }

    /**
     * Sanitize array of permissions
     *
     * @param array $permissions
     *
     * @return array
     *
     * @access private
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _sanitize_permission($permission, $permission_key)
    {
        if (is_string($permission)) {
            $effect = strtolower($permission);
            $result = [
                'effect' => in_array($effect, [ 'allow', 'deny' ]) ? $effect : 'deny'
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
            $result = $permission;
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _normalize_permission($permission, $permission_key)
    {
        return $permission;
    }

}