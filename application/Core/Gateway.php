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
 * @method AAM_Framework_Service_Capabilities capabilities(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Capabilities caps(mixed $runtime_context = null)
 * @method AAM_Framework_Service_Settings settings(mixed $runtime_context = null)
 *
 * @property AAM_Framework_Utility_Cache $cache
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
     * Constructor
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct() {}

    /**
     * Prevent from fatal errors
     *
     * @param string $name
     * @param array  $args
     *
     * @return AAM_Framework_Service_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function __call($name, $args)
    {
        return AAM_Framework_Manager::_()->{$name}(array_shift($args));
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
        return $this->utility($name);
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
        if (is_null($identifier)) {
            $result = AAM::current_user();
        } else {
            $result = $this->access_levels->get(
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
        return $this->access_levels->get(
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
        return $this->access_levels->get(AAM_Framework_Type_AccessLevel::VISITOR);
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
        return $this->access_levels->get(AAM_Framework_Type_AccessLevel::DEFAULT);
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
     * Return utility instance
     *
     * @param string $utility_name
     *
     * @return AAM_Framework_Utility_Interface
     */
    public function utility($utility_name)
    {
        return AAM_Framework_Manager::_()->{$utility_name};
    }

    /**
     * Get collection of posts
     *
     * @param AAM_Framework_Resource_Interface $parent_resource
     * @param array                            $args            [Optional]
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
            AAM_Framework_Manager::load($default_context);
        }
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
        $subject = null,
        $skipInheritance = false
    ) {
        if (is_null($subject)) {
            $subject = AAM::current_user();
        }

        if (AAM::api()->config->get(
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