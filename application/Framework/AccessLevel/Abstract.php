<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract layer for the access level
 *
 * Access Level (AL) is something that invokes WordPress resources like posts, menus,
 * URIs, etc. In other words, AL is the abstract access and security layer that
 * contains set of settings that define how end user or visitor access a requested
 * resource.
 *
 * ALs are related in the hierarchical way where "Default" AL supersede all
 * other ALs and access & security settings are propagated down the tree.
 *
 * AL can have siblings and they are located on the same hierarchical level and access
 * settings get merged based on predefined preference. The example of sibling is a
 * user that has two or more roles. In this case the first role is primary while all
 * other roles are siblings to it.
 *
 * Some ALs have reference to the core instance of the underlying WordPress core user
 * or role.
 *
 * @package AAM
 *
 * @version 6.9.34
 */
abstract class AAM_Framework_AccessLevel_Abstract
{

    /**
     * Core native instance of a subject
     *
     * This property holds an instance of a core object without abstracted AAM layer.
     * This way you have the ability to access methods and properties of the native
     * instance (e.g. WP_User or WP_Role)
     *
     * @var object
     *
     * @access private
     * @version 6.9.34
     */
    private $_core_instance = null;

    /**
     * Array of already instantiated resources
     *
     * @var array
     *
     * @access private
     * @version 6.9.34
     */
    private $_resources = [];

    /**
     * Constructor
     *
     * @param object $core_instance
     *
     * @return void
     *
     * @access public
     * @version 6.9.34
     */
    public function __construct(object $core_instance = null)
    {
        $this->_core_instance = $core_instance;

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_access_level_methods_filter', [], $this, $core_instance
        );

        if (is_array($closures)) {
            foreach($closures as $closure) {
                $closure->bindTo($this);
            }
        }
    }

    /**
     * Get resource by its type and internal ID
     *
     * @param string     $resource_type
     * @param string|int $resource_id
     * @param boolean    $skip_inheritance
     *
     * @return AAM_Framework_Resource_Abstract|null
     *
     * @access public
     * @version 6.9.34
     * @todo Remove some functionality after migration to AAM Framework is completed
     */
    public function get_resource(
        $resource_type, $resource_id = null, $skip_inheritance = false
    ) {
        $resource  = null;
        $suffix    = ($skip_inheritance ? '_direct' : '_full');
        $cache_key = $resource_type . $resource_id . $suffix;

        // Is resource with specified internal ID already instantiated?
        if (!isset($this->_resources[$cache_key])) {
            $resource = $this->_init_resource(
                $resource_type, $resource_id, $skip_inheritance
            );

            if (is_object($resource)) {
                do_action("aam_initialized_{$resource_type}_resource_action",
                    $resource
                );

                // Finally cache the instance of the resource
                $this->_resources[$cache_key] = $resource;
            }
        } else {
            $resource = $this->_resources[$cache_key];
        }

        return $resource;
    }

    /**
     * Initialize resource
     *
     * This method will trigger the inheritance mechanism unless $skip_inheritance
     * is set to false
     *
     * @param string  $resource_type
     * @param mixed   $resource_id
     * @param boolean $skip_inheritance
     *
     * @return AAM_Framework_Resource_Abstract|null
     *
     * @access private
     * @version 6.9.34
     */
    private function _init_resource(
        $resource_type, $resource_id, $skip_inheritance
    ) {
        $resource = apply_filters(
            'aam_get_resource_filter',
            null,
            $this,
            $resource_type,
            $resource_id,
        );

        if (is_object($resource)) {
            // Kick in the inheritance chain if needed
            if ($skip_inheritance !== true) {
                $this->_inherit_from_parent($resource);
            }
        }

        return $resource;
    }

    /**
     * Inherit settings from parent access level (if any)
     *
     * @param AAM_Framework_Resource_Abstract $resource
     *
     * @return array
     *
     * @access protected
     * @version 6.9.34
     */
    private function _inherit_from_parent($resource)
    {
        $parent = $this->get_parent();

        if (is_object($parent)) {
            $parent_resource = $parent->get_resource(
                $resource::TYPE,
                $resource->get_internal_id()
            );

            $settings = $parent_resource->get_settings();

            // Merge access settings if multi-roles option is enabled
            $mu_role_support = AAM::api()->configs()->get_config(
                'core.settings.multiSubject'
            );

            if ($mu_role_support && $parent->has_siblings()) {
                foreach ($parent->get_siblings() as $sibling) {
                    $sibling_resource = $sibling->get_resource(
                        $resource::TYPE,
                        $resource->get_internal_id()
                    );

                    $settings = $sibling_resource->merge_settings(
                        $settings, $parent_resource
                    );
                }
            }

            // Merge access settings while reading hierarchical chain
            $settings = array_replace_recursive(
                $settings, $resource->get_settings()
            );

            // Finally set the option for provided object
            $resource->set_settings($settings);
        }

        return $resource->get_settings();
    }

    /**
     * Retrieve parent access level
     *
     * Returns null, ff there is no parent access level
     *
     * @return AAM_Framework_AccessLevel_Abstract|null
     *
     * @access public
     * @version 6.9.34
     */
    abstract public function get_parent();

    /**
     * Check if current subject has specified capability
     *
     * @param string $capability
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.28
     */
    public function can($capability)
    {

    }

    /**
     * Alias for the `can` method
     *
     * @see AAM_Framework_AccessLevel_Abstract::can
     * @version 6.9.28
     */
    public function has_cap($capability)
    {
        return $this->can($capability);
    }

    /**
     * Alias for the `can` method
     *
     * @see AAM_Framework_AccessLevel_Abstract::can
     * @version 6.9.28
     */
    public function has_capability($capability)
    {
        return $this->can($capability);
    }

    public function is_allowed($resource)
    {

    }

    public function is_denied($resource)
    {
        return !$this->allowed($resource);
    }

    public function is_allowed_to($action, $resource)
    {

    }

    public function is_denied_to($action, $resource)
    {
        return !$this->is_allowed_to($action, $resource);
    }

    public function deny($resource)
    {

    }

    public function allow($resource)
    {

    }

    public function deny_to($action, $resource, $settings = null)
    {

    }

    public function allow_to($action, $resource, $settings = null)
    {

    }

    public function reset($resource = null, $action = null)
    {

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
     * @since 6.9.34
     */
    public function __call($name, $arguments)
    {
        $response = null;

        if (method_exists($this->_core_instance, $name)) {
            $response = call_user_func_array(
                array($this->_core_instance, $name), $arguments
            );
        } else {
            _doing_it_wrong(
                static::class . '::' . $name,
                'Method does not exist or is not accessible',
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
     * @version 6.9.34
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

}