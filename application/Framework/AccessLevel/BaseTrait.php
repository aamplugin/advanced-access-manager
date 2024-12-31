<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Base trait for the access level
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
 * @version 7.0.0
 */
trait AAM_Framework_AccessLevel_BaseTrait
{

    /**
     * AAM framework proxy instance
     *
     * This property holds an instance of a object that is an abstract layer between
     * AAM and WordPress core. This way you have the ability to access methods and
     * properties of the native instance (e.g. WP_User or WP_Role) and take advantage
     * of AAM enhancements
     *
     * @var object
     *
     * @access private
     * @version 7.0.0
     */
    private $_proxy_instance = null;

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
     * Collection of siblings
     *
     * Sibling is access level that is on the same level. For example, access level
     * role can have siblings when user is assigned to multiple roles.
     *
     * @var array
     *
     * @version 7.0.0
     */
    private $_siblings = [];

    /**
     * Constructor
     *
     * @param object $core_instance
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct($core_instance = null)
    {
        // Extend access level with more methods
        $closures = apply_filters(
            'aam_access_level_methods_filter', [], $this, $core_instance
        );

        if (is_array($closures)) {
            foreach($closures as $name => $closure) {
                $closures[$name] = $closure->bindTo($this, $this);
            }

            $this->_extended_methods = $closures;
        }

        if (method_exists($this, 'initialize')) {
            $this->initialize($core_instance);
        };
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
        } else {
            if (AAM_Framework_Manager::_()->has_service($name)) {
                $response = AAM_Framework_Manager::_()->{$name}($this);
            } elseif (is_object($this->_proxy_instance)) {
                $response = call_user_func_array(
                    array($this->_proxy_instance, $name), $arguments
                );
            } else {
                throw new RuntimeException(
                    sprintf('Method %s does not exist', $name)
                );
            }
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

        if (is_object($this->_proxy_instance)) {
            $response = $this->_proxy_instance->{$name};
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
    public function get_resource(
        $resource_type,
        $resource_id = null,
        $skip_inheritance = false
    ) {
        $resource = apply_filters(
            'aam_get_resource_filter',
            null,
            $this,
            $resource_type,
            $resource_id,
        );

        if (is_object($resource)) {
            if ($skip_inheritance !== true) {
                // Kick in the inheritance chain if needed
                $this->_inherit_from_parent_resource($resource);

                // Trigger initialized action only when we are at the bottom of
                // the inheritance chain
                if (in_array(get_class($this), [
                    AAM_Framework_AccessLevel_User::class,
                    AAM_Framework_AccessLevel_Visitor::class
                ], true)) {
                    do_action(
                        "aam_init_{$resource_type}_resource_action", $resource
                    );
                }
            }
        }

        return $resource;
    }


    /**
     * @inheritDoc
     */
    public function get_preference(
        $preference_ns,
        $skip_inheritance = false
    ) {
        $preference = new AAM_Framework_Preference_Container($this, $preference_ns);

        if ($skip_inheritance !== true) {
            // Kick in the inheritance chain if needed
            $this->_inherit_from_parent_preference($preference);

            // Trigger initialized action only when we are at the bottom of
            // the inheritance chain
            if (in_array(get_class($this), [
                AAM_Framework_AccessLevel_User::class,
                AAM_Framework_AccessLevel_Visitor::class
            ], true)) {
                do_action('aam_init_preference_action', $preference, $preference_ns);
            }
        }

        return $preference;
    }

    /**
     * @inheritDoc
     */
    public function get_proxy_instance()
    {
        return $this->_proxy_instance;
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function add_sibling(AAM_Framework_AccessLevel_Interface $sibling)
    {
        array_push($this->_siblings, $sibling);
    }

    /**
     * @inheritDoc
     */
    public function has_siblings()
    {
        return count($this->_siblings) > 0;
    }

    /**
     * @inheritDoc
     */
    public function get_siblings()
    {
        return $this->_siblings;
    }

    /**
     * Inherit settings from parent access level (if any)
     *
     * @param AAM_Framework_Resource_Interface $resource
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    private function _inherit_from_parent_resource($resource)
    {
        $parent = $this->get_parent();

        if (is_object($parent)) {
            // Merge access settings if multi access levels config is enabled
            $multi_support = AAM_Framework_Manager::_()->config->get(
                'core.settings.multi_access_levels'
            );

            if ($multi_support && $parent->has_siblings()) {
                $siblings = $parent->get_siblings();
            } else {
                $siblings = [];
            }

            $resource->set_permissions($this->_prepare_permissions(
                $resource,
                $parent->get_resource(
                    $resource::TYPE,
                    $resource->get_internal_id(false)
                ),
                $siblings
            ), false);
        }
    }

    /**
     * Inherit preferences from parent access level (if any)
     *
     * @param AAM_Framework_Preference_Container $preference
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    private function _inherit_from_parent_preference($preference)
    {
        $parent = $this->get_parent();

        if (is_object($parent)) {
            // Merge access settings if multi access levels config is enabled
            $multi_support = AAM_Framework_Manager::_()->config->get(
                'core.settings.multi_access_levels'
            );

            if ($multi_support && $parent->has_siblings()) {
                $siblings = $parent->get_siblings();
            } else {
                $siblings = [];
            }

            $preference->set_preferences($this->_prepare_preferences(
                $preference,
                $parent->get_preference($preference->get_ns()),
                $siblings
            ), false);
        }
    }

    /**
     * Prepare resource's preferences
     *
     * @param AAM_Framework_Preference_Container $preference
     * @param AAM_Framework_Preference_Container $parent_preference
     * @param array                              $siblings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_preferences(
        $preference, $parent_preference, $siblings = []
    ) {
        $preferences = $parent_preference->get_preferences();

        foreach ($siblings as $sibling) {
            $sibling_resource = $sibling->get_preference($preference->get_ns());
            $preferences      = AAM_Framework_Manager::_()->misc->merge_preferences(
                $sibling_resource->get_preferences(), $preferences
            );
        }

        // Merge preferences while reading hierarchical chain but only
        // replace the top keys. Do not replace recursively
        return array_replace($preferences, $preference->get_preferences());
    }

    /**
     * Prepare resource's permissions
     *
     * @param AAM_Framework_Resource_Interface $resource
     * @param AAM_Framework_Resource_Interface $parent_resource
     * @param array                            $siblings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_permissions(
        $resource, $parent_resource, $siblings = []
    ) {
        $perms   = $parent_resource->get_permissions();
        $manager = AAM_Framework_Manager::_();

        foreach ($siblings as $sibling) {
            $sib_perms = $sibling->get_resource(
                $resource::TYPE, $resource->get_internal_id(false)
            )->get_permissions();

            // Important part. If resource is aggregate, than assume that there is
            // an additional level in the array of permissions:
            // [resource_id] => [
            //      [permission] => [ ... ]
            // ]
            if ($resource::TYPE === AAM_Framework_Type_Resource::AGGREGATE) {
                // Getting the unique list of resource_ids from each aggregate
                $resource_ids = array_unique([
                    ...array_keys($perms),
                    ...array_keys($sib_perms)
                ]);

                // Iterating over the list of all keys and merge settings accordingly
                foreach($resource_ids as $id) {
                    $perms[$id] = $manager->misc->merge_permissions(
                        array_key_exists($id, $sib_perms) ? $sib_perms[$id] : [],
                        array_key_exists($id, $perms) ? $perms[$id] : [],
                        $resource::TYPE
                    );
                }
            } else {
                $perms = $manager->misc->merge_permissions(
                    $sib_perms,
                    $perms,
                    $resource::TYPE
                );
            }
        }

        // Merge permissions while reading hierarchical chain but only
        // replace the top keys. Do not replace recursively
        return array_replace($perms, $resource->get_permissions());
    }

}