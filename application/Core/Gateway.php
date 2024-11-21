<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API gateway
 *
 * @method AAM_Framework_Service_Roles roles(mixed $runtime_context = null)
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
 * @method AAM_Framework_Service_Content content(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Users users(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities caps(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Configs configs(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Settings settings(mixed $runtime_context = null)
 * @method AAM_Framework_Service_AccessLevels access_levels(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Utility utility(string $utility = null)
 *
 * @package AAM
 * @version 7.0.0
 */
final class AAM_Core_Gateway
{

    /**
     * Single instance of itself
     *
     * @var AAM_Core_Gateway
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
     * Collection of services
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_registered_services = [];

    /**
     * Constructor
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        $this->_registered_services = apply_filters('aam_api_gateway_services_filter', [
            'roles'                  => AAM_Framework_Service_Roles::class,
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
            'identities'             => AAM_Framework_Service_Identities::class,
            'content'                => AAM_Framework_Service_Content::class,
            'users'                  => AAM_Framework_Service_Users::class,
            'capabilities'           => AAM_Framework_Service_Capabilities::class,
            'caps'                   => AAM_Framework_Service_Capabilities::class,
            'configs'                => AAM_Framework_Service_Configs::class,
            'settings'               => AAM_Framework_Service_Settings::class,
            'access_levels'          => AAM_Framework_Service_AccessLevels::class,
            'utility'                => AAM_Framework_Service_Utility::class
        ]);
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

        if (array_key_exists($name, $this->_registered_services)) {
            $result = $this->_return_service(
                $this->_registered_services[$name], array_shift($args)
            );
        } else {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                "The method {$name} is not defined in the AAM API",
                AAM_VERSION
            );
        }

        return $result;
    }

    /**
     * Get user by their's identifier
     *
     * If no identifier provided, the current user will be return. If user is not
     * authenticated, the visitor access level will be returned.
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_AccessLevel_User|AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function user($identifier = null)
    {
        $service = $this->access_levels();

        if (is_null($identifier)) {
            $result = AAM::current_user();
        } else {
            $result = $service->get(
                AAM_Framework_Type_AccessLevel::USER, $identifier
            );
        }

        return $result;
    }

    /**
     * Get role access level
     *
     * @param string $role_slug
     *
     * @return AAM_Framework_AccessLevel_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function role($role_slug)
    {
        return $this->access_levels()->get(
            AAM_Framework_Type_AccessLevel::ROLE, $role_slug
        );
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function visitor()
    {
        return $this->access_levels()->get(AAM_Framework_Type_AccessLevel::VISITOR);
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function anonymous()
    {
        return $this->visitor();
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function guest()
    {
        return $this->visitor();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function default()
    {
        return $this->access_levels()->get(AAM_Framework_Type_AccessLevel::DEFAULT);
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function all()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function everyone()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function anyone()
    {
        return $this->default();
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function any()
    {
        return $this->default();
    }

    /**
     * Get collection of posts
     *
     * @param AAM_Framework_Resource_Interface $parent_resource
     * @param array                            $args            [optional]
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function posts($parent_resource, $args = [])
    {
        try {
            if (is_a($parent_resource, AAM_Framework_Resource_PostType::class)) {
                $result = $this->_query_post_type_posts($parent_resource, $args);
            } elseif (is_a($parent_resource, AAM_Framework_Resource_Term::class)) {
                $result = $this->_query_term_posts($parent_resource, $args);
            } elseif (is_a($parent_resource, AAM_Framework_Resource_Post::class)) {
                if (is_post_type_hierarchical($parent_resource->post_type)) {
                    $result = $this->_query_post_posts($parent_resource, $args);
                } else {
                    throw new InvalidArgumentException(
                        'The post type is not hierarchical'
                    );
                }
            } else {
                throw new InvalidArgumentException('Invalid parent resource type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get collection of terms
     *
     * @param AAM_Framework_Resource_Interface $parent_resource
     * @param array                            $args            [optional]
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function terms($parent_resource, $args = [])
    {
        try {
            if (is_a($parent_resource, AAM_Framework_Resource_PostType::class)) {
                $result = $this->_query_post_type_terms($parent_resource, $args);
            } elseif (is_a($parent_resource, AAM_Framework_Resource_Taxonomy::class)) {
                $result = $this->_query_taxonomy_terms($parent_resource, $args);
            } elseif (is_a($parent_resource, AAM_Framework_Resource_Term::class)) {
                $result = $this->_query_term_terms($parent_resource, $args);
            } else {
                throw new InvalidArgumentException('Invalid parent resource type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Setup the framework manager
     *
     * @param array $default_context
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function setup(array $default_context = [])
    {
        if (is_array($default_context)) {
            $this->_default_context = $default_context;
        }
    }

    /**
     * Get list of registered services
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_registered_services()
    {
        return $this->_registered_services;
    }

    /**
     * Return an instance of requested service
     *
     * @param string $service_class_name
     * @param mixed  $context
     *
     * @return AAM_Framework_Service_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private function _return_service($service_class_name, $context)
    {
        $new_context = [];

        // Parse the incoming context and determine correct access level
        if (is_a($context, AAM_Framework_AccessLevel_Interface::class)) {
            $new_context = [ 'access_level' => $context ];
        } elseif (is_string($context)) {
            // Trying to parse the context and extract the access level
            if (in_array($context, [ 'visitor', 'anonymous', 'guest'], true)) {
                $new_context = [ 'access_level' => $this->visitor() ];
            } elseif (in_array($context, [ 'default', 'all', 'anyone', 'everyone' ], true)) {
                $new_context = [ 'access_level' => $this->all() ];
            } elseif (strpos($context, ':')) {
                list($access_level, $id) = explode(':', $context, 2);

                if ($access_level === 'role') {
                    $new_context = [ 'access_level' => $this->role($id) ];
                } elseif ($access_level === 'user') {
                    $new_context = [ 'access_level' => $this->user($id) ];
                }
            } else {
                $new_context = $context;
            }
        } elseif (is_array($context)) {
            $new_context = $context;
        }

        // Compile the final context that is passed to the service
        if (is_array($new_context)) {
            $context = array_merge($this->_default_context, $new_context);
        } else {
            $context = $new_context;
        }

        return call_user_func("{$service_class_name}::get_instance", $context);
    }

    /**
     * Get collection of posts
     *
     * @param AAM_Framework_Resource_PostType $resource
     * @param array                           $args     [optional]
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_post_type_posts($resource, $args)
    {
        // Get list of all posts associated with the current term
        $posts = get_posts(array_merge(
            [ 'numberposts' => 1000 ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [ 'fields' => 'ids', 'post_type' => $resource->get_internal_id() ]
        ));

        $result = function () use ($posts, $resource) {
            foreach ($posts as $post_id) {
                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::POST, $post_id
                );
            }
        };

        return $result();
    }

    /**
     * Query term posts
     *
     * @param AAM_Framework_Resource_Term $resource
     * @param array                       $args
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_term_posts($resource, $args)
    {
        $internal_id = $resource->get_internal_id(false);

        if (empty($internal_id['post_type'])) {
            throw new RuntimeException('Term is not initialized with post_type');
        }

        // Get list of all posts associated with the current term
        $posts = get_posts(array_merge(
            [ 'numberposts' => 1000 ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [
                'fields'      => 'ids',
                'post_type'   => $internal_id['post_type'],
                'tax_query'   => [
                    [
                        'taxonomy' => $resource->taxonomy,
                        'field'    => 'slug',
                        'terms'    => $resource->slug
                    ]
                ]
            ]
        ));

        $result = function () use ($posts, $resource) {
            foreach ($posts as $post_id) {
                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::POST, $post_id
                );
            }
        };

        return $result();
    }

    /**
     * Query post child posts
     *
     * @param AAM_Framework_Resource_Post $resource
     * @param array                       $args
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_post_posts($resource, $args)
    {
        // Get list of all posts associated with the current term
        $posts = get_posts(array_merge(
            [ 'numberposts' => 1000 ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [
                'fields'      => 'ids',
                'post_parent' => $resource->get_internal_id(),
                'post_type'   => $resource->post_type
            ]
        ));

        $result = function () use ($posts, $resource) {
            foreach ($posts as $post_id) {
                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::POST, $post_id
                );
            }
        };

        return $result();
    }

    /**
     * Get collection of terms
     *
     * @param AAM_Framework_Resource_PostType $resource
     * @param array                           $args     [optional]
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_post_type_terms($resource, $args)
    {
        // Get list of all terms associated with the current taxonomy
        $terms = get_terms(array_merge(
            [ 'number'   => 1000, 'hide_empty' => false ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [ 'taxonomy' => get_object_taxonomies($resource->get_internal_id()) ]
        ));

        $result = function () use ($terms, $resource) {
            foreach ($terms as $term) {
                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::TERM, [
                        'id'        => $term->term_id,
                        'taxonomy'  => $term->taxonomy,
                        'post_type' => $resource->get_internal_id()
                    ]
                );
            }
        };

        return $result();
    }

    /**
     * Get collection of terms
     *
     * @param AAM_Framework_Resource_Taxonomy $resource
     * @param array                           $args     [optional]
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_taxonomy_terms($resource, $args)
    {
        // Get list of all terms associated with the current taxonomy
        $terms = get_terms(array_merge(
            [ 'number'   => 1000, 'hide_empty' => false ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [ 'taxonomy' => $resource->get_internal_id(), 'fields' => 'ids' ]
        ));

        $result = function () use ($terms, $resource) {
            foreach ($terms as $term_id) {
                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::TERM, [
                        'id'        => $term_id,
                        'taxonomy'  => $resource->get_internal_id()
                    ]
                );
            }
        };

        return $result();
    }

    /**
     * Get collection of child terms
     *
     * @param AAM_Framework_Resource_Term $resource
     * @param array                       $args     [optional]
     *
     * @return Generator
     *
     * @access private
     * @version 7.0.0
     */
    private function _query_term_terms($resource, $args)
    {
        // Get list of all terms associated with the current taxonomy
        $terms = get_terms(array_merge(
            [ 'number'   => 1000, 'hide_empty' => false ],
            $args,
            // Making sure that two critical aspects of querying can't be overwritten
            [ 'parent' => $resource->term_id, 'taxonomy' => $resource->taxonomy ]
        ));

        $result = function () use ($terms, $resource) {
            $internal_id = $resource->get_internal_id();

            foreach ($terms as $term) {
                // Compile the compound ID
                $compound_id = [
                    'id'        => $term->term_id,
                    'taxonomy'  => $term->taxonomy,
                ];

                if (!empty($internal_id['post_type'])) {
                    $compound_id['post_type'] = $internal_id['post_type'];
                }

                yield $resource->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::TERM, $compound_id
                );
            }
        };

        return $result();
    }







    /**
     * Prepare Access Policy manager but only if service is enabled
     *
     * @param AAM_Core_Subject $subject
     * @param boolean          $skipInheritance
     *
     * @return AAM_Core_Policy_Manager|null
     *
     * @since 6.1.0 Added $skipInheritance flag to insure proper settings inheritance
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     * @deprecated
     */
    public function getAccessPolicyManager(
        AAM_Core_Subject $subject = null, $skipInheritance = false
    ) {
        if (is_null($subject)) {
            $subject = AAM::current_user();
        }

        if (AAM::api()->configs()->get_config(
            AAM_Service_AccessPolicy::FEATURE_FLAG
        )) {
            $manager = AAM_Core_Policy_Factory::get($subject, $skipInheritance);
        } else {
            $manager = null;
        }

        return $manager;
    }

    /**
     * Reset all AAM settings and configurations
     *
     * @return void
     *
     * @access public
     *
     * @version 6.9.6
     * @deprecated
     */
    public function reset()
    {
        AAM_Core_API::clearSettings();
    }

    /**
     * Merge two set of access settings into one
     *
     * The merging method also takes in consideration the access settings preference
     * defined in ConfigPress
     *
     * @param array  $set1
     * @param array  $set2
     * @param string $objectType
     * @param string $preference
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     * @deprecated 7.0.0 Moved to resource abstract
     */
    public function mergeSettings($set1, $set2, $objectType, $preference = null)
    {
        $merged = array();

        // If preference is not explicitly defined, fetch it from the AAM configs
        if (is_null($preference)) {
            $default_preference = $this->configs()->get_config(
                'core.settings.merge.preference'
            );

            $preference = $this->configs()->get_config(
                "core.settings.{$objectType}.merge.preference",
                $default_preference
            );
        }

        // First get the complete list of unique keys
        $keys = array_keys($set1);
        foreach (array_keys($set2) as $key) {
            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        foreach ($keys as $key) {
            // There can be only two types of preferences: "deny" or "allow". Based
            // on that, choose access settings that have proper effect as following:
            //
            //   - If set1 and set2 have two different preferences, get the one that
            //     has correct preference;
            //   - If set1 and set2 have two the same preferences, choose the set2
            //   - If only set1 has access settings, use set1 as-is
            //   - If only set2 has access settings, use set2 as-is
            //   - If set1 and set2 have different effect than preference, choose
            //     set2
            $effect1 = $this->computeAccessOptionEffect($set1, $key);
            $effect2 = $this->computeAccessOptionEffect($set2, $key);
            $effect  = ($preference === 'deny');

            // Access Option is either boolean true or array with "enabled" key
            // set as boolean true
            if ($effect1 === $effect2) { // both equal
                $merged[$key] = $set2[$key];
            } elseif ($effect1 === $effect) { // set1 matches preference
                $merged[$key] = $set1[$key];
            } elseif ($effect2 === $effect) { // set2 matches preference
                $merged[$key] = $set2[$key];
            } else {
                if ($preference === 'allow') {
                    $option = isset($set2[$key]) ? $set2[$key] : $set1[$key];
                    if (is_array($option)) {
                        $option['enabled'] = false;
                    } else {
                        $option = false;
                    }
                    $merged[$key] = $option;
                } elseif (is_null($effect1)) {
                    $merged[$key] = $set2[$key];
                } elseif (is_null($effect2)) {
                    $merged[$key] = $set1[$key];
                }
            }
        }

        return $merged;
    }

    /**
     * Determine correct access option effect
     *
     * There can be two possible types of the access settings: straight boolean and
     * array with "enabled" flag. If provided key is not a part of the access options,
     * the null is returned, otherwise boolean true of false.
     *
     * @param array  $opts
     * @param string $key
     *
     * @return null|boolean
     *
     * @access protected
     * @version 6.0.0
     * @deprecated 7.0.0
     */
    protected function computeAccessOptionEffect($opts, $key)
    {
        $effect = null; // nothing is defined

        if (isset($opts[$key])) {
            $effect = is_array($opts[$key]) ? $opts[$key]['enabled'] : $opts[$key];
        }

        return $effect;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Core_Gateway
     *
     * @access public
     * @version 7.0.0
     */
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}