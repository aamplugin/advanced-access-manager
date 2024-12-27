<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Content (aka Posts & Terms) service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Content
{

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.content.enabled';

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'service.content.enabled'               => true,
        'service.content.manage_all_post_types' => false,
        'service.content.manage_all_taxonomies' => false
    ];

    /**
     * Collection of post type caps
     *
     * This is a collection of post type capabilities for optimization reasons. It
     * is used by _map_meta_cap method to determine if additional check needs to be
     * perform
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_content_capabilities = array(
        'edit_post', 'edit_page', 'read_post', 'read_page', 'publish_post'
    );

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->config->get(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Content::register();
                });

                // Check if Access Manager metabox feature is enabled
                $metaboxEnabled = AAM::api()->config->get(
                    'core.settings.ui.render_access_metabox'
                );

                if ($metaboxEnabled) {
                    // Make sure that all already registered taxonomies are hooked
                    foreach(get_taxonomies() as $taxonomy) {
                        add_action(
                            "{$taxonomy}_edit_form_fields",
                            function($term) {
                                if (is_a($term, 'WP_Term')
                                    && current_user_can('aam_manage_content')
                                ) {
                                    $view = AAM_Backend_View::get_instance();

                                    echo $view->renderTermMetabox($term);
                                }
                            }
                        );
                    }

                    // Hook into still up-coming taxonomies down the pipeline
                    add_action('registered_taxonomy', function($taxonomy) {
                        add_action(
                            "{$taxonomy}_edit_form_fields",
                            function($term) {
                                if (is_a($term, 'WP_Term')) {
                                    $view = AAM_Backend_View::get_instance();

                                    echo $view->renderTermMetabox($term);
                                }
                            }
                        );
                    });

                    // Register custom access control metabox
                    add_action(
                        'add_meta_boxes',
                        function() {
                            $this->_register_access_manager_metabox();
                        }
                    );
                }
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Posts & Terms', AAM_KEY),
                    'description' => __('Manage access to your website content for any user, role or visitor. This include access to posts, pages, media attachment, custom post types, categories, tags, custom taxonomies and terms.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if ($enabled) {
            // Register RESTful API
            AAM_Restful_ContentService::bootstrap();

            $this->initialize_hooks();
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)) {
                    if ($resource_type === AAM_Framework_Type_Resource::POST) {
                        $resource = new AAM_Framework_Resource_Post(
                            $access_level, $resource_id
                        );
                    } elseif ($resource_type === AAM_Framework_Type_Resource::POST_TYPE) {
                        $resource = new AAM_Framework_Resource_PostType(
                            $access_level, $resource_id
                        );
                    } elseif ($resource_type === AAM_Framework_Type_Resource::TAXONOMY) {
                        $resource = new AAM_Framework_Resource_Taxonomy(
                            $access_level, $resource_id
                        );
                    } elseif ($resource_type === AAM_Framework_Type_Resource::TERM) {
                        $resource = new AAM_Framework_Resource_Term(
                            $access_level, $resource_id
                        );
                    }
                }

                return $resource;
            }, 10, 4
        );
    }

    /**
     * Initialize Content service hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        if (!is_admin()) {
            // Password protected filter
            add_filter('post_password_required', function($result, $post) {
                return $this->_is_password_protected($result, $post);
            }, 10, 2);

            // Manage password check expiration
            add_filter('post_password_expires', function($result) {
                return $this->_post_password_expires($result);
            });

            // Filter navigation pages & taxonomies
            add_filter('wp_get_nav_menu_items', function($pages) {
                return $this->_get_nav_menu_items($pages);
            }, PHP_INT_MAX);

            // Manage access to frontend posts & pages
            add_action('wp', function() {
                global $wp_query;

                if ($wp_query->is_single || $wp_query->is_page) {
                    $this->_authorize_post_access();
                }
            }, PHP_INT_MAX);
        }

        // Ability to share visibility settings with other solutions
        add_filter('aam_get_content_visibility_filter', function($result) {
            if (empty($result)) {
                $result = AAM_Service_Content_Visibility::bootstrap()->get_settings();
            }

            return $result;
        });

        // Control post visibility
        add_filter('posts_clauses_request', function($clauses, $query) {
            return $this->_posts_clauses_request($clauses, $query);
        }, 10, 2);

        // Evaluate if current user can see full content or only a teaser message
        add_filter(
            'the_content',
            function($content) {
                return $this->_the_content($content);
            }, PHP_INT_MAX
        );

        // Evaluate if user can comment on a post
        add_filter('comments_open', function ($open, $post_id) {
            $resource = AAM::api()->content()->get_post($post_id);

            // If Leave Comments option is defined then override the default status.
            // Otherwise keep it as-is
            if ($resource->is_allowed_to('comment') === false) {
                $open = false;
            }

            return $open;
        }, 10, 2);

        // Check if user has ability to perform certain task based on provided
        // capability and meta data
        add_filter('map_meta_cap', function($caps, $cap, $_, $args) {
            return $this->_map_meta_cap($caps, $cap, $args);
        }, PHP_INT_MAX, 4);

        // REST API action authorization. Triggered before call is dispatched
        add_filter(
            'rest_request_before_callbacks',
            function($response, $_, $request) {
                return $this->_rest_request_before_callbacks($response, $request);
            }, 10, 3
        );

        // Audit all registered post types and adjust access controls accordingly
        add_action('registered_post_type', function ($post_type, $obj) {
            // REST API. Control if user is allowed to publish content
            add_filter("rest_pre_insert_{$post_type}", function ($post, $request) {
                $status = (isset($request['status']) ? $request['status'] : null);

                if (in_array($status, array('publish', 'future'), true)) {
                    $resource = AAM::api()->content()->get_post($request['id']);

                    if ($resource->is_denied_to('publish') === false) {
                        $post = new WP_Error(
                            'rest_cannot_publish',
                            __('You are not allowed to publish this content', AAM_KEY),
                            array('status' => rest_authorization_required_code())
                        );
                    }
                }

                return $post;
            }, 10, 2);

            // Populate the collection of post type caps
            foreach ([ 'edit_post', 'read_post', 'delete_post', 'publish_posts' ] as $cap) {
                $meta_cap = $obj->cap->{$cap};

                if (!empty($meta_cap)
                    && !in_array($meta_cap, $this->_content_capabilities, true)
                    && ($meta_cap !== 'do_not_allow')
                ) {
                    $this->_content_capabilities[] = $cap;
                }
            }
        }, 10, 2);
    }

    /**
     * Get current post
     *
     * @return AAM_Framework_Resource_Post|null
     *
     * @access public
     *
     * @global WP_Query $wp_query
     * @global WP_Post  $post
     * @version 7.0.0
     */
    public function get_current_post()
    {
        $post = AAM::api()->misc->get_current_post();

        if (is_a($post, 'WP_Post')) {
            $result = AAM::api()->content()->get_post($post->ID);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Main frontend access control hook
     *
     * @return void
     *
     * @access private
     * @global WP_Query $wp_query
     *
     * @version 7.0.0
     */
    private function _authorize_post_access()
    {
        $post = $this->get_current_post();

        if (is_a($post, AAM_Framework_Resource_Post::class)) {
            if ($post->is_restricted()) {
                AAM::api()->redirect->do_access_denied_redirect();
            } elseif ($post->is_redirected()) {
                AAM::api()->redirect->do_redirect($post->get_redirect());
            }
        }
    }

    /**
     * Check if post is password protected
     *
     * This callback is used by the Frontend to determine if current post requires
     * password in order to see its content
     *
     * @param boolean $result
     * @param WP_Post $post
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_password_protected($result, $post)
    {
        // Honor the manually set password on the post
        if (($result === false) && is_a($post, 'WP_Post')) {
            $resource = AAM::api()->content()->get_post($post->ID);
            $check    = $this->_verify_post_password($resource);
            $result   = is_wp_error($check);
        }

        return $result;
    }

    /**
     * Check PASSWORD PROTECTED access option
     *
     * If post has password set, return WP_Error so the application can do further
     * authorization process.
     *
     * @param AAM_Framework_Resource_Post $post
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    private function _verify_post_password(AAM_Framework_Resource_Post $post)
    {
        $result = true;

        if ($post->is_password_protected()) {
            // If password is empty or not provided, try to read it from the cookie.
            // This is the default WordPress behavior when it comes to password
            // protected posts/pages
            $is_matched = AAM_Core_API::prepareHasher()->CheckPassword(
                $post->get_password(),
                wp_unslash(
                    $this->getFromCookie('wp-postpass_' . COOKIEHASH)
                )
            );

            if ($is_matched === false) {
                $result = new WP_Error(
                    'rest_unauthorized',
                    'The post is password protected. Invalid password provided.',
                    array('status' => 401)
                );
            }
        }

        return $result;
    }

    /**
     * Redefine entered password TTL
     *
     * @param int $expire
     *
     * @return int
     *
     * @access private
     * @version 7.0.0
     */
    private function _post_password_expires($expire)
    {
        $ttl = AAM::api()->config->get(
            'service.content.password_ttl', null
        );

        return !empty($ttl) ? time() + strtotime($ttl) : $expire;
    }

    /**
     * Register Access Manager metabox on post edit screen
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _register_access_manager_metabox()
    {
        global $post;

        if (is_a($post, 'WP_Post')) {
            add_meta_box(
                'aam-access-manager',
                __('Access Manager', AAM_KEY),
                function () {
                    global $post;

                    echo AAM_Backend_View::renderPostMetabox($post);
                },
                null,
                'advanced',
                'high'
            );
        }
    }

    /**
     * Filter traditional navigation menu
     *
     * @param array $pages
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_nav_menu_items($pages)
    {
        if (is_array($pages)) {
            $service = AAM::api()->content();

            foreach ($pages as $i => $page) {
                if (in_array($page->type, array('post_type', 'custom'), true)) {
                    if ($service->get_post($page->object_id)->is_hidden()) {
                        unset($pages[$i]);
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * After post SELECT query
     *
     * @param array    $clauses
     * @param WP_Query $wpQuery
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _posts_clauses_request($clauses, $wp_query)
    {
        static $executing = false;

        if (!$wp_query->is_singular && !$executing) {
            $executing  = true;
            $visibility = AAM_Service_Content_Visibility::bootstrap();

            $clauses['where'] .= apply_filters(
                'aam_content_visibility_where_clause_filter',
                $visibility->prepare_post_query($wp_query),
                $wp_query,
                $visibility->get_settings()
            );

            $executing = false;
        }

        return $clauses;
    }

    /**
     * Authorize RESTful action before it is dispatched by RESTful Server
     *
     * @param mixed  $response
     * @param object $request
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    private function _rest_request_before_callbacks($response, $request)
    {
        // Register hooks that check post access
        foreach (get_post_types(array('show_in_rest' => true)) as $type) {
            add_filter(
                "rest_prepare_{$type}", function($response, $post, $request) {
                    return $this->_authorize_post_rest_access(
                        $response, $post, $request
                    );
                }, 10, 3
            );
        }

        // Override the password authentication handling ONLY for posts
        $attrs      = $request->get_attributes();
        $callback   = isset($attrs['callback']) ? $attrs['callback'] : null;
        $controller = (is_array($callback) ? array_shift($callback) : null);

        if (is_a($controller, 'WP_REST_Posts_Controller')) {
            $post     = get_post($request['id']);
            $has_pass = isset($request['password']);

            // Honor the manually defined password on the post
            if (is_a($post, 'WP_Post')
                    && empty($post->post_password)
                    && $has_pass
                    && ($request->get_method() === 'GET')
            ) {
                $request['_password'] = $request['password'];
                unset($request['password']);
            }
        }

        return $response;
    }

    /**
     * Check if post is allowed to be viewed through RESTful
     *
     * @param WP_REST_Response $response
     * @param WP_Post          $post
     * @param WP_REST_Request  $request
     *
     * @access public
     * @version 7.0.0
     */
    private function _authorize_post_rest_access($response, $post, $request)
    {
        $resource = AAM::api()->content()->get_post($post->ID);

        if ($resource->is_password_protected()) {
            $password = isset($request['_password']) ? $request['_password'] : null;

            if ($resource->get_password() !== $password) {
                $response->set_status(401);
                $response->set_data([
                    'code'    => 'rest_unauthorized',
                    'message' => 'The post is password protected. Invalid password provided.'
                ]);
            }
        } elseif ($resource->is_redirected()) {
            $redirect = $resource->get_redirect();

            // Determine redirect HTTP status code and use it if applicable for given
            // redirect type
            if (!empty($redirect['http_status_code'])) {
                $status_code = $redirect['http_status_code'];
            } else {
                $status_code = 307;
            }

            $response->set_status($status_code);
            $response->set_data([
                'code'    => 'rest_redirected',
                'message' => 'The request is redirected to a different location',
                'data'    => [
                    'redirect_url' => AAM::api()->redirect->to_redirect_url(
                        $redirect
                    )
                ]
            ]);
        } elseif ($resource->is_restricted()) {
            $response->set_status(401);
            $response->set_data([
                'code'    => 'rest_unauthorized',
                'message' => 'The content is restricted.'
            ]);
        }

        return $response;
    }

    /**
     * Filter post content
     *
     * @param string $content
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    private function _the_content($content)
    {
        static $in = false;

        if (!$in) {
            $in   = true;
            $post = $this->get_current_post();

            if (is_a($post, AAM_Framework_Resource_Post::class)
                && $post->is_teased()
            ) {
                // Replace the [excerpt] placeholder with posts excerpt and do
                // short-code evaluation
                $content = do_shortcode(str_replace(
                    '[excerpt]', $post->post_excerpt, $post->get_teaser_message()
                ));

                // Decorate message
                $content = apply_filters('the_content', $content);
            }

            $in = false;
        }

        return $content;
    }

    /**
     * Check user capability
     *
     * This is a hack function that add additional layout on top of WordPress
     * core functionality. Based on the capability passed in the $args array as
     * "0" element, it performs additional check on user's capability to manage
     * post, users etc.
     *
     * @param array  $caps
     * @param string $cap
     * @param array  $args
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_meta_cap($caps, $cap, $args)
    {
        global $post;

        // For optimization reasons, check only caps that belong to registered post
        // types
        if (in_array($cap, $this->_content_capabilities, true)) {
            // Critical part of the implementation. We do not know ahead what
            // capability is responsible for what action when it comes to post types.
            if (isset($args[0]) && is_scalar($args[0])) {
                $post_id = intval($args[0]);
            } elseif (is_a($post, 'WP_Post')) {
                $post_id = $post->ID;
            } else {
                $post_id = null;
            }

            // If post_id is not empty, then, potentially we are checking
            // permission to perform one of the action against a post
            if (!empty($post_id)) {
                $post = get_post($post_id);

                if (is_a($post, 'WP_Post')) {
                    $post_type = get_post_type_object($post->post_type);

                    if (is_a($post_type, 'WP_Post_Type')) {
                        $caps = $this->__map_post_type_caps(
                            $post_type,
                            $cap,
                            $caps,
                            $post,
                            $args
                        );
                    }
                }
            }
        }

        return $caps;
    }

    /**
     * Map post type capability based on set permissions
     *
     * @param WP_Post_Type $post_type
     * @param string       $cap
     * @param array        $caps
     * @param WP_Post      $post
     * @param array        $args
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function __map_post_type_caps(
        WP_Post_Type $post_type,
        $cap,
        $caps,
        WP_Post $post,
        $args
    ) {
        // Cover the scenario when $cap is not part of the post type capabilities
        // There is a bug in the WP core when user is checked for 'publish_post'
        // capability
        $primitive_cap = array_search($cap, (array) $post_type->cap);

        if ($primitive_cap === false) {
            $primitive_cap = $cap;
        }

        switch ($primitive_cap) {
            case 'edit_post':
            case 'edit_posts':
            case 'edit_others_posts':
            case 'edit_private_posts':
            case 'edit_published_posts':
                // Cover the scenario when user uses Bulk Action or Quick Edit to
                // change the Status to Published and post is not allowed to be
                // published
                $action = AAM_Core_Request::request('action');
                $status = AAM_Core_Request::request('_status');

                if (
                    in_array($action, [ 'edit', 'inline-save'] , true)
                    && $status === 'publish'
                ) {
                    $caps = $this->_map_publish_post_caps($caps, $post->ID);
                } else {
                    $caps = $this->_map_edit_post_caps($caps, $post->ID);
                }
                break;

            case 'delete_post':
            case 'delete_posts':
            case 'delete_private_posts':
            case 'delete_published_posts':
            case 'delete_others_posts':
                $caps = $this->_map_delete_post_caps($caps, $post->ID);
                break;

            case 'read_post':
            case 'read_private_posts':
            case 'read':
                $password = (isset($args[1]) ? $args[1] : null);
                $caps     = $this->_map_read_post_caps($caps, $post->ID, $password);
                break;

            case 'publish_post':
            case 'publish_posts':
                $caps = $this->_map_publish_post_caps($caps, $post->ID);
                break;

            default:
                break;
        }

        return $caps;
    }

    /**
     * Mutate capability meta map based on ability to publish the post
     *
     * @param array $caps
     * @param int   $post_id
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_publish_post_caps($caps, $post_id)
    {
        $resource = AAM::api()->content()->get_post($post_id);

        if ($resource->is_denied_to('publish') === true) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Mutate capability meta map based on ability to edit/update the post
     *
     * @param array $caps
     * @param int   $post_id
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_edit_post_caps($caps, $post_id)
    {
        $resource = AAM::api()->content()->get_post($post_id);
        $is_draft = $resource->post_status === 'auto-draft';

        if (!$is_draft && ($resource->is_denied_to('edit') === true)) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Mutate capability meta map based on ability to trash/delete the post
     *
     * @param array $caps
     * @param int   $post_id
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_delete_post_caps($caps, $post_id)
    {
        $resource = AAM::api()->content()->get_post($post_id);

        if ($resource->is_denied_to('delete') === true) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Mutate capability meta map based on ability to edit/update the post
     *
     * @param array       $caps
     * @param int         $post_id
     * @param string|null $password
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_read_post_caps($caps, $post_id, $password = null)
    {
        $post = AAM::api()->content()->get_post($post_id);

        if ($post->is_password_protected()) {
            if ($post->get_password() !== $password) {
                $caps[] = 'do_not_allow';
            }
        } elseif ($post->is_restricted()) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

}