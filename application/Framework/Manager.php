<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Framework manager
 *
 * @method AAM_Framework_Service_Urls urls(mixed $runtime_context = null)
 * @method AAM_Framework_Service_ApiRoutes api_routes(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Jwts jwts(mixed $runtime_context = null)
 * @method AAM_Framework_Service_LoginRedirect login_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_LogoutRedirect logout_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_NotFoundRedirect not_found_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_BackendMenu backend_menu(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AdminToolbar admin_toolbar(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Metaboxes metaboxes(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Widgets widgets(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AccessDeniedRedirect access_denied_redirect(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Identities identities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Posts posts(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Terms terms(mixed $runtime_context = null)
 * @method AAM_Framework_Service_PostTypes post_types(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Taxonomies taxonomies(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities caps(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Settings settings(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Policies policies(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Hooks hooks(mixed $runtime_context = null)
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
 *
 * @package AAM
 * @version 7.0.0
 */
final class AAM_Framework_Manager
{

    /**
     * Single instance of itself
     *
     * @var AAM_Framework_Manager
     *
     * @access private
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Default context shared by all services
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_default_context = [];

    /**
     * Collection of utilities
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_utilities = [
        'cache'         => AAM_Framework_Utility_Cache::class,
        'object_cache'  => AAM_Framework_Utility_ObjectCache::class,
        'misc'          => AAM_Framework_Utility_Misc::class,
        'config'        => AAM_Framework_Utility_Config::class,
        'redirect'      => AAM_Framework_Utility_Redirect::class,
        'capabilities'  => AAM_Framework_Utility_Capabilities::class,
        'caps'          => AAM_Framework_Utility_Capabilities::class,
        'roles'         => AAM_Framework_Utility_Roles::class,
        'users'         => AAM_Framework_Utility_Users::class,
        'db'            => AAM_Framework_Utility_Db::class,
        'access_levels' => AAM_Framework_Utility_AccessLevels::class,
        'jwt'           => AAM_Framework_Utility_Jwt::class,
        'policy'        => AAM_Framework_Utility_Policy::class
    ];

    /**
     * Collection of utilities
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_services = [
        'urls'                   => AAM_Framework_Service_Urls::class,
        'api_routes'             => AAM_Framework_Service_ApiRoutes::class,
        'jwts'                   => AAM_Framework_Service_Jwts::class,
        'login_redirect'         => AAM_Framework_Service_LoginRedirect::class,
        'logout_redirect'        => AAM_Framework_Service_LogoutRedirect::class,
        'not_found_redirect'     => AAM_Framework_Service_NotFoundRedirect::class,
        'backend_menu'           => AAM_Framework_Service_BackendMenu::class,
        'admin_toolbar'          => AAM_Framework_Service_AdminToolbar::class,
        'metaboxes'              => AAM_Framework_Service_Metaboxes::class,
        'widgets'                => AAM_Framework_Service_Widgets::class,
        'access_denied_redirect' => AAM_Framework_Service_AccessDeniedRedirect::class,

        // TODO: Remove these
        'content'                => AAM_Framework_Service_Content::class,
        'identities'             => AAM_Framework_Service_Identities::class,

        'posts'                  => AAM_Framework_Service_Posts::class,
        'terms'                  => AAM_Framework_Service_Terms::class,
        'post_types'             => AAM_Framework_Service_PostTypes::class,
        'taxonomies'             => AAM_Framework_Service_Taxonomies::class,
        'capabilities'           => AAM_Framework_Service_Capabilities::class,
        'caps'                   => AAM_Framework_Service_Capabilities::class,
        'settings'               => AAM_Framework_Service_Settings::class,
        'policies'               => AAM_Framework_Service_Policies::class,
        'hooks'                  => AAM_Framework_Service_Hooks::class
    ];

    /**
     * Collection of resources
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_resources = [
        AAM_Framework_Type_Resource::TOOLBAR      => AAM_Framework_Resource_AdminToolbar::class,
        AAM_Framework_Type_Resource::API_ROUTE    => AAM_Framework_Resource_ApiRoute::class,
        AAM_Framework_Type_Resource::BACKEND_MENU => AAM_Framework_Resource_BackendMenu::class,
        AAM_Framework_Type_Resource::POST         => AAM_Framework_Resource_Post::class,
        AAM_Framework_Type_Resource::POST_TYPE    => AAM_Framework_Resource_PostType::class,
        AAM_Framework_Type_Resource::TAXONOMY     => AAM_Framework_Resource_Taxonomy::class,
        AAM_Framework_Type_Resource::TERM         => AAM_Framework_Resource_Term::class,
        AAM_Framework_Type_Resource::AGGREGATE    => AAM_Framework_Resource_Aggregate::class,
        AAM_Framework_Type_Resource::USER         => AAM_Framework_Resource_User::class,
        AAM_Framework_Type_Resource::ROLE         => AAM_Framework_Resource_Role::class,
        AAM_Framework_Type_Resource::METABOX      => AAM_Framework_Resource_Metabox::class,
        AAM_Framework_Type_Resource::URL          => AAM_Framework_Resource_Url::class,
        AAM_Framework_Type_Resource::WIDGET       => AAM_Framework_Resource_Widget::class,
        AAM_Framework_Type_Resource::HOOK         => AAM_Framework_Resource_Hook::class
    ];

    /**
     * Construct
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_action('init', function () {
            if ($this->config->get('service.policies.enabled', true)) {
                // Register JSON Access Policy CPT
                register_post_type(AAM_Framework_Service_Policies::CPT, [
                    'label'        => 'Access Policy',
                    'labels'       => [
                        'name'          => 'Access Policies',
                        'edit_item'     => 'Edit Policy',
                        'singular_name' => 'Policy',
                        'add_new_item'  => 'Add New Policy',
                        'new_item'      => 'New Policy'
                    ],
                    'public'              => false,
                    'show_ui'             => true,
                    'show_in_rest'        => true,
                    'show_in_menu'        => false,
                    'exclude_from_search' => true,
                    'publicly_queryable'  => false,
                    'hierarchical' => false,
                    'supports'     => [
                        'title', 'excerpt', 'revisions', 'custom-fields'
                    ],
                    'delete_with_user' => false,
                    'capabilities' => [
                        'edit_post'         => 'aam_edit_policy',
                        'read_post'         => 'aam_read_policy',
                        'delete_post'       => 'aam_delete_policy',
                        'delete_posts'      => 'aam_delete_policies',
                        'edit_posts'        => 'aam_edit_policies',
                        'edit_others_posts' => 'aam_edit_others_policies',
                        'publish_posts'     => 'aam_publish_policies',
                    ]
                ]);
            }
        });

        // Load list of resources that framework manages
        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)
                    && array_key_exists($resource_type, $this->_resources)
                ) {
                    $resource = new $this->_resources[$resource_type](
                        $access_level, $resource_id
                    );
                }

                return $resource;
            }, 10, 4
        );
    }

    /**
     * Prevent from fatal errors
     *
     * @param string $name
     * @param array  $args
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        $result = null;

        if (array_key_exists($name, $this->_services)) {
            $runtime_context = array_shift($args);

            // Parse the incoming context and determine correct access level
            if (is_a($runtime_context, AAM_Framework_AccessLevel_Interface::class)) {
                $context = [ 'access_level' => $runtime_context ];
            } elseif (is_string($runtime_context)) {
                $context = $this->_string_to_context($runtime_context);
            } elseif (is_array($runtime_context)) {
                $context = $runtime_context;
            } else {
                $context = [];
            }

            // Compile the final context that is passed to the service
            if (is_array($context)) {
                $context = array_merge($this->_default_context, $context);
            } else {
                throw new InvalidArgumentException(
                    'Invalid service context provided'
                );
            }

            $result = call_user_func(
                "{$this->_services[$name]}::get_instance", $context
            );
        } else {
            throw new BadMethodCallException(sprintf(
                'There is no service %s defined', $name
            ));
        }

        return $result;
    }

    /**
     * Get utility instance
     *
     * @param string $name
     *
     * @return AAM_Framework_Utility_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_utilities)) {
            $result = $this->_utilities[$name]::bootstrap();
        } else {
            throw new BadMethodCallException(sprintf(
                'There is no utility %s defined', $name
            ));
        }

        return $result;
    }

    /**
     * Check if provided service name is registered
     *
     * @param string $name
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function has_service($name)
    {
        return array_key_exists($name, $this->_services);
    }

    /**
     * Check if provided utility name is registered
     *
     * @param string $name
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function has_utility($name)
    {
        return array_key_exists($name, $this->_utilities);
    }

    /**
     * Convert a string to a context
     *
     * @param string $str
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _string_to_context($str)
    {
        // Trying to parse the context and extract the access level
        if (in_array($str, [ 'visitor', 'anonymous', 'guest'], true)) {
            $context = [
                'access_level' => $this->access_levels->get_visitor()
            ];
        } elseif (in_array($str, [ 'default', 'all', 'anyone', 'everyone' ], true)) {
            $context = [
                'access_level' => $this->access_levels->get_default()
            ];
        } elseif (strpos($str, ':')) {
            list($access_level, $id) = explode(':', $str, 2);

            if ($access_level === 'role') {
                $context = [
                    'access_level' => $this->access_levels->get_role($id)
                ];
            } elseif ($access_level === 'user') {
                $context = [
                    'access_level' => $this->access_levels->get_user($id)
                ];
            }
        } else {
            throw new InvalidArgumentException(
                'Unsupported access level string value'
            );
        }

        return $context;
    }

    /**
     * Get single instance of itself
     *
     * @param array $default_context [Optional]
     *
     * @return AAM_Framework_Manager
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function load(array $default_context = [])
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        // Set the default context if it is not empty
        if (!empty($default_context)) {
            self::$_instance->_default_context = $default_context;
        }

        return self::$_instance;
    }

    /**
     * Get instance of the framework manager without providing context
     *
     * @return AAM_Framework_Manager
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function _()
    {
        return self::load();
    }

}