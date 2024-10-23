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
     * Inherited permissions from parent access level (if applicable)
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_inherited_permissions = [];

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
     * @param mixed|null                          $internal_id
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $internal_id = null
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
        $settings = AAM_Framework_Manager::settings([
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
        if ($explicit_only) {
            // First, settings the explicit permissions
            $this->_explicit_permissions = $permissions;

            // Overriding the final set of settings
            $this->_permissions = array_merge($this->_permissions, $permissions);

            // Store changes in DB
            $result = AAM_Framework_Manager::settings([
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
    public function get_permission($permission_key)
    {
        if (array_key_exists($permission_key, $this->_permissions)) {
            $result = $this->_permissions[$permission_key];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set_permission($permission_key, $permission)
    {
        return $this->set_permissions(array_merge(
            $this->_explicit_permissions,
            [ $permission_key => $permission ]
        ));
    }

    /**
     * @inheritDoc
     */
    public function is_overwritten()
    {
        return !empty($this->_explicit_permissions);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->_explicit_permissions = [];

        return AAM_Framework_Manager::settings([
            'access_level' => $this->get_access_level()
        ])->delete_setting($this->_get_settings_ns());
    }

    /**
     * Merge incoming permissions
     *
     * Depending on the resource type, different strategies may be applied to merge
     * permissions
     *
     * @param array $incoming
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_permissions($incoming)
    {
        $result = [];
        $config = AAM_Framework_Manager::configs();

        // If preference is not explicitly defined, fetch it from the AAM configs
        $preference = $config->get_config(
            'core.settings.merge.preference'
        );

        $preference = $config->get_config(
            'core.settings.' . constant('static::TYPE') . '.merge.preference',
            $preference
        );

        $base = $this->_permissions;

        // First get the complete list of unique keys
        $rule_keys = array_unique([
            ...array_keys($incoming),
            ...array_keys($base)
        ]);

        foreach($rule_keys as $rule_key) {
            $result[$rule_key] = $this->_merge_permissions(
                isset($base[$rule_key]) ? $base[$rule_key] : null,
                isset($incoming[$rule_key]) ? $incoming[$rule_key] : null,
                $preference
            );
        }

        return $result;
    }

    /**
     * Merge two rules based on provided preference
     *
     * @param array|null $base
     * @param array|null $incoming
     * @param string     $preference
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _merge_permissions($base, $incoming, $preference = 'deny')
    {
        $result   = null;
        $effect_a = null;
        $effect_b = null;

        if (!empty($base)) {
            $effect_a = $base['effect'] === 'allow';
        }

        if (!empty($incoming)) {
            $effect_b = $incoming['effect'] === 'allow';
        }

        if ($preference === 'allow') { // Merging preference is to allow
            // If at least one set has allowed rule, then allow the URL
            if (in_array($effect_a, [ true, null ], true)
                || in_array($effect_b, [ true, null ], true)
            ) {
                $result = [ 'effect' => 'allow' ];
            } elseif (!is_null($effect_a)) { // Is base rule set has URL defined?
                $result = $base;
            } else {
                $result = $incoming;
            }
        } else { // Merging preference is to deny access by default
            if ($effect_a === false) {
                $result = $base;
            } elseif ($effect_b === false) {
                $result = $incoming;
            } else {
                $result = [ 'effect' => 'allow' ];
            }
        }

        return $result;
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
        // Determine the namespace for resource settings within all settings
        $resource_id = $this->get_internal_id();

        // Compile the namespace
        $ns  = constant('static::TYPE');
        $ns .= (is_null($resource_id) ? '' : ".{$resource_id}");

        return $ns;
    }

}