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
 * @method AAM_Framework_Service_Identities identities(mixed $access_level = null, array $settings = [])
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
        $skip_inheritance = false,
        $reload = null
    ) {
        $result = $this->_get_cached_resource(
            $resource_type, $resource_id, $skip_inheritance, $reload
        );

        if (is_null($result)) {
            $result = apply_filters(
                'aam_get_resource_filter',
                null,
                $this,
                $resource_type,
                $resource_id,
            );

            if (is_object($result)) {
                if ($skip_inheritance !== true) {
                    // Kick in the inheritance chain if needed
                    $this->_inherit_from_parent_resource($result);

                    // Trigger initialized action only when we are at the bottom of
                    // the inheritance chain
                    if (in_array(get_class($this), [
                        AAM_Framework_AccessLevel_User::class,
                        AAM_Framework_AccessLevel_Visitor::class
                    ], true)) {
                        do_action(
                            "aam_init_{$resource_type}_resource_action", $result
                        );
                    }
                }

                $this->_set_cached_resource(
                    $resource_type, $resource_id, $skip_inheritance, $result
                );
            }
        }

        return $result;
    }


    /**
     * @inheritDoc
     */
    public function get_preference(
        $preference_ns,
        $skip_inheritance = false,
        $reload = null
    ) {
        $result = $this->_get_cached_preference(
            $preference_ns, $skip_inheritance, $reload
        );

        if (is_null($result)) {
            $result = new AAM_Framework_Preference_Container($this, $preference_ns);

            if ($skip_inheritance !== true) {
                // Kick in the inheritance chain if needed
                $this->_inherit_from_parent_preference($result);

                // Trigger initialized action only when we are at the bottom of
                // the inheritance chain
                if (in_array(get_class($this), [
                    AAM_Framework_AccessLevel_User::class,
                    AAM_Framework_AccessLevel_Visitor::class
                ], true)) {
                    do_action('aam_init_preference_action', $result, $preference_ns);
                }
            }

            $this->_set_cached_preference(
                $preference_ns, $skip_inheritance, $result
            );
        }

        return $result;
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
     * Get already cached resource
     *
     * @param string $resource_type
     * @param mixed  $resource_id
     * @param bool   $skip_inheritance
     * @param bool   $reload
     *
     * @return null|AAM_Framework_Resource_Interface
     */
    private function _get_cached_resource(
        $resource_type, $resource_id, $skip_inheritance, $reload
    ) {
        $result = null;

        if ($reload !== true) {
            // Determine if we can use cache
            $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
                'core.settings.object_cache.ignore.resource_types', ''
            ));

            if (!in_array($resource_type, $exclude, true)) {
                $result = AAM_Framework_Manager::_()->object_cache->get([
                    constant('static::TYPE'),
                    $this->get_id(),
                    $resource_type,
                    $resource_id,
                    $skip_inheritance ? 'partial' : 'full'
                ]);
            }
        }

        return $result;
    }

    /**
     * Get already cached resource
     *
     * @param string $resource_type
     * @param mixed  $resource_id
     * @param bool   $skip_inheritance
     * @param mixed  $obj
     *
     * @return bool
     */
    private function _set_cached_resource(
        $resource_type, $resource_id, $skip_inheritance, $obj
    ) {
        $result = true;

        $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
            'core.settings.object_cache.ignore.resource_types', ''
        ));

        if (!in_array($resource_type, $exclude, true)) {
            $result = AAM_Framework_Manager::_()->object_cache->set([
                constant('static::TYPE'),
                $this->get_id(),
                $resource_type,
                $resource_id,
                $skip_inheritance ? 'partial' : 'full'
            ], $obj);
        }

        return $result;
    }

    /**
     * Get preference object instance cache
     *
     * @param string $ns
     * @param bool   $skip_inheritance
     * @param bool   $reload
     *
     * @return null|AAM_Framework_Preference_Interface
     */
    private function _get_cached_preference($ns, $skip_inheritance, $reload)
    {
        $result = null;

        if ($reload !== true) {
            // Determine if we can use cache
            $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
                'core.settings.object_cache.ignore.preference_types', ''
            ));

            if (!in_array($ns, $exclude, true)) {
                $result = AAM_Framework_Manager::_()->object_cache->get([
                    constant('static::TYPE'),
                    $ns,
                    $skip_inheritance ? 'partial' : 'full'
                ]);
            }
        }

        return $result;
    }

    /**
     * Cache preference object instance
     *
     * @param string $ns
     * @param bool   $skip_inheritance
     * @param mixed  $obj
     *
     * @return bool
     */
    private function _set_cached_preference($ns, $skip_inheritance, $obj)
    {
        $result = true;

        $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
            'core.settings.object_cache.ignore.preference_types', ''
        ));

        if (!in_array($ns, $exclude, true)) {
            $result = AAM_Framework_Manager::_()->object_cache->set([
                constant('static::TYPE'),
                $this->get_id(),
                $ns,
                $skip_inheritance ? 'partial' : 'full'
            ], $obj);
        }

        return $result;
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