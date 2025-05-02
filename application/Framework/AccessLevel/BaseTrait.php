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
 * @method AAM_Framework_Service_Urls urls(array $settings = [])
 * @method AAM_Framework_Service_ApiRoutes api_routes(array $settings = [])
 * @method AAM_Framework_Service_Jwts jwts(array $settings = [])
 * @method AAM_Framework_Service_LoginRedirect login_redirect(array $settings = [])
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(array $settings = [])
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(array $settings = [])
 * @method AAM_Framework_Service_BackendMenu backend_menu(array $settings = [])
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(array $settings = [])
 * @method AAM_Framework_Service_Metaboxes metaboxes(array $settings = [])
 * @method AAM_Framework_Service_Widgets widgets(array $settings = [])
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(array $settings = [])
 * @method AAM_Framework_Service_Roles roles(array $settings = [])
 * @method AAM_Framework_Service_Users users(array $settings = [])
 * @method AAM_Framework_Service_Posts posts(array $settings = [])
 * @method AAM_Framework_Service_Terms terms(array $settings = [])
 * @method AAM_Framework_Service_PostTypes post_types(array $settings = [])
 * @method AAM_Framework_Service_Taxonomies taxonomies(array $settings = [])
 * @method AAM_Framework_Service_Capabilities capabilities(array $settings = [])
 * @method AAM_Framework_Service_Capabilities caps(array $settings = [])
 * @method AAM_Framework_Service_Settings settings(array $settings = [])
 * @method AAM_Framework_Service_Policies policies(array $settings = [])
 * @method AAM_Framework_Service_Hooks hooks(array $settings = [])
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
     * @access private
     *
     * @version 7.0.0
     */
    private $_proxy_instance = null;

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
     * Collection of siblings
     *
     * Sibling is access level that is on the same level. For example, access level
     * role can have siblings when user is assigned to multiple roles.
     *
     * @var array
     * @access private
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
     * @access public
     *
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
        } else {
            if (AAM_Framework_Manager::_()->has_service($name)) {
                $response = AAM_Framework_Manager::_()->{$name}($this, ...$args);
            } elseif (is_object($this->_proxy_instance)) {
                $response = call_user_func_array(
                    array($this->_proxy_instance, $name), $args
                );
            } else {
                throw new RuntimeException(
                    sprintf('Method %s does not exist', esc_js($name))
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
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        $response = null;

        if ($name === 'type') {
            $response = $this->type;
        } elseif (is_object($this->_proxy_instance)) {
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
    public function get_resource($resource_type, $reload = null) {
        $result = $this->_get_cached_instance('resource', $resource_type, $reload);

        if (is_null($result)) {
            $result = apply_filters(
                'aam_get_resource_filter',
                null,
                $this,
                $resource_type
            );

            $this->_set_cached_instance($result);
        }

        return $result;
    }


    /**
     * @inheritDoc
     */
    public function get_preference($preference_type, $reload = null)
    {
        $result = $this->_get_cached_instance(
            'preference', $preference_type, $reload
        );

        if (is_null($result)) {
            $result = apply_filters(
                'aam_get_preference_filter',
                null,
                $this,
                $preference_type
            );

            $this->_set_cached_instance($result);
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
     * Get already cached resource or preference instance
     *
     * @param string $instance_type
     * @param string $type
     * @param bool   $reload
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_cached_instance($instance_type, $type, $reload) {
        $result = null;

        if ($reload !== true) {
            // Determine if we can use cache
            $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
                "core.settings.object_cache.ignore.{$instance_type}_types", ''
            ));

            if (!in_array($type, $exclude, true)) {
                $result = AAM_Framework_Manager::_()->object_cache->get([
                    $this->type,
                    $this->get_id(),
                    $instance_type,
                    $type
                ]);
            }
        }

        return $result;
    }

    /**
     * Get already cached resource
     *
     * @param mixed $instance
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _set_cached_instance($instance)
    {
        $result = true;

        if (!empty($instance)) {
            if (is_a($instance, AAM_Framework_Resource_Interface::class)) {
                $instance_type = 'resource';
            } else {
                $instance_type = 'preference';
            }

            $type = $instance->type;

            $exclude = wp_parse_list(AAM_Framework_Manager::_()->config->get(
                "core.settings.object_cache.ignore.{$instance_type}_types", ''
            ));

            if (!in_array($type, $exclude, true)) {
                $result = AAM_Framework_Manager::_()->object_cache->set([
                    $this->type,
                    $this->get_id(),
                    $instance_type,
                    $type
                ], $instance);
            }
        }

        return $result;
    }

}