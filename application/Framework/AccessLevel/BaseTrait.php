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
 * @method AAM_Framework_Service_Roles roles()
 * @method AAM_Framework_Service_Urls urls()
 * @method AAM_Framework_Service_ApiRoutes api_routes()
 * @method AAM_Framework_Service_Jwts jwts()
 * @method AAM_Framework_Service_LoginRedirect login_redirect()
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect()
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect()
 * @method AAM_Framework_Service_BackendMenu backend_menu()
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar()
 * @method AAM_Framework_Service_Metaboxes metaboxes()
 * @method AAM_Framework_Service_Widgets widgets()
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect()
 * @method AAM_Framework_Service_Identities identities()
 * @method AAM_Framework_Service_Content content()
 * @method AAM_Framework_Service_Users users()
 * @method AAM_Framework_Service_Capabilities capabilities()
 * @method AAM_Framework_Service_Capabilities caps()
 * @method AAM_Framework_Service_Configs configs()
 * @method AAM_Framework_Service_Settings settings()
 * @method AAM_Framework_Service_AccessLevels access_levels()
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
            $services = AAM::api()->get_registered_services();

            if (array_key_exists($name, $services)) {
                $response = call_user_func("{$services[$name]}::get_instance", [
                    'access_level' => $this
                ]);
            } elseif (is_object($this->_proxy_instance)) {
                $response = call_user_func_array(
                    array($this->_proxy_instance, $name), $arguments
                );
            } else {
                _doing_it_wrong(
                    static::class . '::' . $name,
                    'Method does not exist',
                    AAM_VERSION
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
                $this->_inherit_from_parent($resource);

                // Trigger initialized action only when we are at the bottom of
                // the inheritance chain
                if (in_array(get_class($resource->get_access_level()), [
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
     * @param object $resource
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    private function _inherit_from_parent($resource)
    {
        $parent        = $this->get_parent();
        $is_permission = is_a(
            $resource, AAM_Framework_Resource_PermissionInterface::class
        );

        if (is_object($parent)) {
            // Merge access settings if multi access levels config is enabled
            $multi_support = AAM::api()->configs()->get_config(
                'core.settings.multi_access_levels'
            );

            if ($multi_support && $parent->has_siblings()) {
                $siblings = $parent->get_siblings();
            } else {
                $siblings = [];
            }

            if ($is_permission) {
                $resource->set_permissions($this->_prepare_permissions(
                    $resource,
                    $parent->get_resource(
                        $resource::TYPE,
                        $resource->get_internal_id(false)
                    ),
                    $siblings
                ), false);
            } else {
                $resource->set_preferences($this->_prepare_preferences(
                    $resource,
                    $parent->get_resource($resource::TYPE, null),
                    $siblings
                ), false);
            }
        }
    }

    /**
     * Prepare resource's preferences
     *
     * @param AAM_Framework_Resource_PreferenceInterface $resource
     * @param AAM_Framework_Resource_PreferenceInterface $parent_resource
     * @param array                                      $siblings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_preferences(
        $resource, $parent_resource, $siblings = []
    ) {
        $preferences = $parent_resource->get_preferences();

        foreach ($siblings as $sibling) {
            $sibling_resource = $sibling->get_resource($resource::TYPE, null);
            $preferences      = $sibling_resource->merge_preferences($preferences);
        }

        // Merge preferences while reading hierarchical chain but only
        // replace the top keys. Do not replace recursively
        return array_replace($preferences, $resource->get_preferences());
    }

    /**
     * Prepare resource's permissions
     *
     * @param AAM_Framework_Resource_PermissionInterface $resource
     * @param AAM_Framework_Resource_PermissionInterface $parent_resource
     * @param array                                      $siblings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_permissions(
        $resource, $parent_resource, $siblings = []
    ) {
        $permissions = $parent_resource->get_permissions();

        foreach ($siblings as $sibling) {
            $sibling_resource = $sibling->get_resource(
                $resource::TYPE, $resource->get_internal_id(false)
            );

            $permissions = $sibling_resource->merge_permissions($permissions);
        }

        // Merge permissions while reading hierarchical chain but only
        // replace the top keys. Do not replace recursively
        return array_replace($permissions, $resource->get_permissions());
    }

}