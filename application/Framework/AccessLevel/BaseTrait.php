<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use MaxMind\Db\Reader\InvalidDatabaseException;

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
     * Array of already instantiated resources or preferences
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_repository = [];

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
        $reload = false,
        $skip_inheritance = false
    ) {
        $resource  = null;

        if (is_scalar($resource_id) || is_null($resource_id)) {
            $cache_key = $resource_type . $resource_id;
        } elseif (is_array($resource_id)) {
            $cache_key = $resource_type . implode('', array_values($resource_id));
        } else {
            throw new InvalidDatabaseException(
                'The resource_id has to be either scalar value or an array'
            );
        }

        if ($skip_inheritance) {
            $cache_key .= '_direct';
        }

        // Is resource with specified internal ID already instantiated?
        if (!isset($this->_repository[$cache_key]) || $reload) {
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
                    $this->_inherit_from_parent($resource, $reload);

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

                // Finally cache the instance of the resource
                $this->_repository[$cache_key] = $resource;
            }
        } else {
            $resource = $this->_repository[$cache_key];
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
     * @inheritDoc
     */
    public function urls()
    {
        return AAM_Framework_Manager::urls([
            'access_level' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function login_redirect()
    {
        return AAM_Framework_Manager::login_redirect([
            'access_level' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function logout_redirect()
    {
        return AAM_Framework_Manager::logout_redirect([
            'access_level' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function access_denied_redirect()
    {
        return AAM_Framework_Manager::access_denied_redirect([
            'access_level' => $this
        ]);
    }

     /**
     * @inheritDoc
     */
    public function not_found_redirect()
    {
        return AAM_Framework_Manager::not_found_redirect([
            'access_level' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function content()
    {
        return AAM_Framework_Manager::content([
            'access_level' => $this
        ]);
    }

    /**
     * Inherit settings from parent access level (if any)
     *
     * @param object  $resource
     * @param boolean $reload
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    private function _inherit_from_parent($resource, $reload)
    {
        $parent        = $this->get_parent();
        $is_permission = is_a(
            $resource, AAM_Framework_Resource_PermissionInterface::class
        );

        if (is_object($parent)) {
            $parent_resource = $parent->get_resource(
                $resource::TYPE,
                $is_permission ? $resource->get_internal_id(false) : null,
                $reload
            );

            $settings = $parent_resource->get_settings();

            // Merge access settings if multi access levels config is enabled
            $multi_support = AAM::api()->configs()->get_config(
                'core.settings.multi_access_levels'
            );

            if ($multi_support && $parent->has_siblings()) {
                foreach ($parent->get_siblings() as $sibling) {
                    $sibling_resource = $sibling->get_resource(
                        $resource::TYPE,
                        $is_permission ? $resource->get_internal_id(false) : null
                    );

                    $settings = $sibling_resource->merge_settings($settings);
                }
            }

            // Merge access settings while reading hierarchical chain
            $settings = array_replace_recursive(
                $settings, $resource->get_settings()
            );

            // Finally set the settings
            $resource->set_settings($settings);
        }

        return $resource->get_settings();
    }

}