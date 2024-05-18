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
 * @version 6.9.28
 */
abstract class AAM_Framework_AccessLevel_Abstract
{

    /**
     * Type of the subject
     *
     * Has to be overwritten by the class
     *
     * @param string
     * @version 6.9.28
     */
    const TYPE = 'abstract';

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
     * @version 6.9.28
     */
    private $_core_instance;

    /**
     * Array of already instantiated resources
     *
     * @var array
     *
     * @access private
     * @version 6.9.28
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
     * @version 6.9.28
     */
    public function __construct(object $core_instance = null)
    {
        $this->_core_instance = $core_instance;

        // Extend access level with more methods
        $closures = apply_filters(
            'aam_access_level_methods_filter', [], static::TYPE, $core_instance
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
     * @version 6.9.28
     * @todo Remove some functionality after migration to AAM Framework is completed
     */
    public function get_resource(
        $resource_type, $resource_id = null, $skip_inheritance = false
    ) {
        $suffix      = ($skip_inheritance ? '_direct' : '_full');
        $internal_id = $resource_type . $resource_id . $suffix;

        // Is resource with specified internal ID already instantiated?
        if (!isset($this->_resources[$internal_id])) {
            $resource = apply_filters(
                'aam_get_resource_filter',
                null,
                $this,
                $resource_type,
                $resource_id,
            );

            // TODO: Remove in AAM 7.0.0
            if (is_null($resource)) {
                $resource = apply_filters(
                    'aam_object_filter',
                    null,
                    $this,
                    $resource_type,
                    $resource_id,
                    $skip_inheritance
                );
            }

            if (is_object($resource)) {
                // Kick in the inheritance chain if needed
                if ($skip_inheritance !== true) {
                    $this->_inherit_from_parent($resource);
                }

                // TODO: Remove in AAM 7.0.0
                $resource = apply_filters(
                    "aam_initialized_{$resource_type}_object_filter",
                    $resource
                );

                // Finally cache the instance of the resource
                $this->_resources[$internal_id] = $resource;
            }
        } else {
            $resource = $this->_resources[$internal_id];
        }

        return $resource;
    }

    /**
     * Inherit access settings for provided object from the parent subject(s)
     *
     * @param AAM_Framework_Resource_Abstract $resource
     *
     * @return array
     *
     * @access protected
     * @version 6.9.28
     * @todo Replace with commented version after migration to Framework
     */
    // private function _inherit_from_parent($resource)
    // {
    //     $parent = $this->get_parent();

    //     if (is_object($parent)) {
    //         $parent_resource = $parent->get_resource(
    //             $resource::TYPE,
    //             $resource->get_internal_id()
    //         );

    //         $settings = $parent_resource->get_settings();

    //         // Merge access settings if multi-roles option is enabled
    //         $multi_roles = AAM::api()->config()->get(
    //             'core.settings.multiSubject', false
    //         );

    //         if ($multi_roles && $parent->has_siblings()) {
    //             foreach ($parent->get_siblings() as $sibling) {
    //                 $sibling_resource = $sibling->get_resource(
    //                     $resource::TYPE,
    //                     $resource->get_internal_id()
    //                 );

    //                 $settings = $sibling_resource->merge(
    //                     $parent_resource->get_settings(), $parent_resource
    //                 );
    //             }
    //         }

    //         // Merge access settings while reading hierarchical chain
    //         $settings = array_replace_recursive(
    //             $settings, $resource->get_settings()
    //         );

    //         // Finally set the option for provided object
    //         $resource->set_settings($settings);
    //     }

    //     return $resource->get_settings();
    // }
    private function _inherit_from_parent($resource)
    {
        $parent = $this->get_parent();

        if (is_object($parent)) {
            $parent_resource = $parent->get_resource(
                $resource::OBJECT_TYPE,
                $resource->getId()
            );

            $option = $parent_resource->getOption();

            // Merge access settings if multi-roles option is enabled
            $mu_role_support = AAM::api()->getConfig(
                'core.settings.multiSubject',
                false
            );

            if ($mu_role_support && $parent->has_siblings()) {
                foreach ($parent->get_siblings() as $sibling) {
                    $obj = $sibling->get_resource(
                        $resource::OBJECT_TYPE,
                        $resource->getId()
                    );

                    $option = $obj->mergeOption($option, $parent_resource);
                }
            }

            // Merge access settings while reading hierarchical chain
            $option = array_replace_recursive($option, $resource->getOption());

            // Finally set the option for provided object
            $resource->setOption($option);
        }

        return $resource->getOption();
    }

    /**
     * Retrieve parent access level
     *
     * Returns null, ff there is no parent access level
     *
     * @return AAM_Framework_AccessLevel_Abstract|null
     *
     * @access public
     * @version 6.9.28
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


    // public function add_cap($capability)
    // {

    // }

    // public function add_capability($capability)
    // {
    //     return $this->add_cap($capability);
    // }

    // public function remove_cap($capability)
    // {

    // }

    // public function remove_capability($capability)
    // {
    //     return $this->remove_cap($capability);
    // }

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

    public function policy($policy_id)
    {

    }

    // public function policies()
    // {

    // }

    // public function admin_menu($menu_id = null)
    // {

    // }

    // public function toolbar($toolbar_id = null)
    // {

    // }

    // public function metabox($metabox_id)
    // {

    // }

    // public function metaboxes()
    // {

    // }

    // public function widget($widget_id)
    // {

    // }

    // public function widgets()
    // {

    // }

    // public function capabilities()
    // {

    // }

    public function access_denied_redirect($area)
    {

    }

    public function access_denied_redirects()
    {

    }

    // public function login_redirect()
    // {

    // }

    // public function logout_redirect()
    // {

    // }

    public function not_found_redirect()
    {

    }

    public function api_routes()
    {

    }

    public function api_route($route_id)
    {

    }

    public function urls()
    {

    }

    public function url($url_id)
    {

    }

    /**
     * Get post object
     *
     * @param int|WP_Post $post_identifier
     *
     * @return AAM_Framework_Object_Post
     */
    public function post($post_identifier)
    {

    }

    // /**
    //  * Get posts service
    //  *
    //  * @return AAM_Framework_Service_Posts
    //  */
    // public function posts()
    // {

    // }

    // /**
    //  * Get terms service
    //  *
    //  * @return AAM_Framework_Service_Terms
    //  */
    // public function terms()
    // {

    // }

    // /**
    //  * Get taxonomies service
    //  *
    //  * @return AAM_Framework_Service_Taxonomies
    //  */
    // public function taxonomies()
    // {

    // }

    // /**
    //  * Get post types service
    //  *
    //  * @return AAM_Framework_Service_PostTypes
    //  */
    // public function post_types()
    // {

    // }

    /**
     * Proxy method to the legacy AAM subject
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @since 6.9.28
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
     * @version 6.9.28
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