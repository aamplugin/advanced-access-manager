<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Posts & Terms service
 *
 * @since 6.7.7 https://github.com/aamplugin/advanced-access-manager/issues/184
 * @since 6.6.1 https://github.com/aamplugin/advanced-access-manager/issues/137
 * @since 6.5.1 https://github.com/aamplugin/advanced-access-manager/issues/115
 * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.2.0 Enhanced HIDDEN option with more granular access controls
 * @since 6.1.0 Multiple bug fixed
 * @since 6.0.4 Fixed incompatibility with some quite aggressive plugins
 * @since 6.0.2 Refactored the way access to posts is managed. No more pseudo caps
 *              aam|...
 * @since 6.0.1 Bug fixing
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.7
 */
class AAM_Service_Content
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'content';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.content.enabled';

    /**
     * Post view counter
     *
     * @version 6.0.0
     */
    const POST_COUNTER_DB_OPTION = 'aam_post_%s_access_counter';

    /**
     * Collection of post type caps
     *
     * This is a collection of post type capabilities for optimization reasons. It
     * is used by filterMetaMaps method to determine if additional check needs to be
     * perform
     *
     * @var array
     *
     * @access protected
     * @version 6.0.2
     */
    protected $postTypeCaps = array(
        'edit_post', 'edit_page', 'read_post', 'read_page', 'publish_post'
    );

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.5.1 https://github.com/aamplugin/advanced-access-manager/issues/115
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.5.1
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Post::register();
                });

                // Check if Access Manager metabox feature is enabled
                $metaboxEnabled = AAM_Core_Config::get('ui.settings.renderAccessMetabox', true);

                if ($metaboxEnabled && current_user_can('aam_manage_content')) {
                    // Make sure that all already registered taxonomies are hooked
                    foreach(get_taxonomies() as $taxonomy) {
                        add_action(
                            "{$taxonomy}_edit_form_fields",
                            array($this, 'renderAccessTermMetabox')
                        );
                    }

                    // Hook into still up-coming taxonomies down the pipeline
                    add_action('registered_taxonomy', function($taxonomy) {
                        add_action(
                            "{$taxonomy}_edit_form_fields",
                            array($this, 'renderAccessTermMetabox')
                        );
                    });

                    //register custom access control metabox
                    add_action('add_meta_boxes', array($this, 'registerAccessPostMetabox'));
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

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Render Access Manager metabox on term edit screen
     *
     * @param WP_Term $term
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function renderAccessTermMetabox($term)
    {
        if (is_a($term, 'WP_Term')) {
            echo AAM_Backend_View::getInstance()->renderTermMetabox($term);
        }
    }

    /**
     * Register Access Manager metabox on post edit screen
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function registerAccessPostMetabox()
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
     * Initialize Content service hooks
     *
     * @return void
     *
     * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.1.0 Fixed the bug where `do_not_allow` capability was mapped to the
     *              list of post type capabilities
     * @since 6.0.2 Removed invocation for the pseudo-cap mapping for post types
     * @since 6.0.1 Fixed bug related to enabling commenting on all posts
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.0
     */
    protected function initializeHooks()
    {
        if (!is_admin()) {
            // Password protected filter
            add_filter('post_password_required', array($this, 'isPasswordRequired'), 10, 2);

            // Manage password check expiration
            add_filter('post_password_expires', array($this, 'checkPassExpiration'));

            // Filter navigation pages & taxonomies
            add_filter('wp_get_nav_menu_items', array($this, 'getNavigationMenu'), 999);

            // Filter navigation pages & taxonomies
            add_filter('get_pages', array($this, 'filterPages'), 999);

            // Manage access to frontend posts & pages
            add_action('wp', array($this, 'wp'), 999);
        }

        // Control post visibility
        add_filter('posts_clauses_request', array($this, 'filterPostQuery'), 10, 2);

        // Filter post content
        add_filter('the_content', array($this, 'filterPostContent'), 999);

        // Check if user has ability to perform certain task based on provided
        // capability and meta data
        add_filter('map_meta_cap', array($this, 'filterMetaMaps'), 999, 4);

        // Get control over commenting stuff
        add_filter('comments_open', function ($open, $id) {
            $object = AAM::getUser()->getObject('post', $id);

            // If Leave Comments option is defined then override the default status.
            // Otherwise keep it as-is
            if ($object->isDefined('comment')) {
                $open = $object->isAllowedTo('comment');
            }

            return $open;
        }, 10, 2);

        // REST API action authorization. Triggered before call is dispatched
        add_filter('rest_request_before_callbacks', array($this, 'beforeDispatch'), 10, 3);

        // REST API. Control if user is allowed to publish content
        add_action('registered_post_type', function ($post_type, $obj) {
            add_filter("rest_pre_insert_{$post_type}", function ($post, $request) {
                $status = (isset($request['status']) ? $request['status'] : null);

                if (in_array($status, array('publish', 'future'), true)) {
                    if ($this->isAuthorizedToPublishPost($request['id']) === false) {
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
            foreach ($obj->cap as $cap) {
                if (
                    !in_array($cap, $this->postTypeCaps, true)
                    && ($cap !== 'do_not_allow')
                ) {
                    $this->postTypeCaps[] = $cap;
                }
            }
        }, 10, 2);

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        );

        // Share post access settings conversion with add-ons and other third-party
        // solutions
        add_filter('aam_post_policy_generator_filter', function($list, $res, $opts) {
            return array_merge(
                $list, $this->_convertToPostStatements($res, $opts)
            );
        }, 10, 3);

        // Service fetch
        $this->registerService();
    }

    /**
     * Generate Post policy statements
     *
     * @param array                     $policy
     * @param string                    $resource_type
     * @param array                     $options
     * @param AAM_Core_Policy_Generator $generator
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_Post::OBJECT_TYPE) {
            if (!empty($options)) {
                $statements = array();

                foreach($options as $id => $data) {
                    $parts    = explode('|', $id);
                    $post     = get_post($parts[0]);

                    if (is_a($post, 'WP_Post')) {
                        $resource = "Post:{$parts[1]}:{$post->post_name}";

                        $statements = array_merge(
                            $statements,
                            $this->_convertToPostStatements($resource, $data)
                        );
                    }
                }

                $policy['Statement'] = array_merge($policy['Statement'], $statements);
            }
        }

        return $policy;
    }

    /**
     * Convert post settings to policy format
     *
     * @param string $resource
     * @param array  $options
     *
     * @return array
     *
     * @since 6.4.0 Moved this method from AAM_Core_Policy_Generator
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/22
     * @since 6.2.2 Fixed bug that caused fatal error for PHP lower than 7.0.0
     * @since 6.2.0 Initial implementation of the method
     *
     * @access private
     * @version 6.4.0
     */
    private function _convertToPostStatements($resource, $options)
    {
        $tree = (object) array(
            'allowed'    => array(),
            'denied'     => array(),
            'statements' => array()
        );

        foreach($options as $option => $settings) {
            // Compute Effect property
            if (is_bool($settings)) {
                $effect = ($settings === true ? 'denied' : 'allowed');
            } else {
                $effect = (!empty($settings['enabled']) ? 'denied' : 'allowed');
            }

            $action = null;

            switch($option) {
                case 'restricted':
                    $action = 'Read';
                    break;

                case 'comment':
                case 'edit':
                case 'delete':
                case 'publish':
                case 'create':
                    $action = ucfirst($option);
                    break;

                case 'hidden':
                    $item = array(
                        'Effect'  => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'List',
                        'Resource' => $resource
                    );

                    $conditions = array();

                    if (is_array($settings)) {
                        if (!empty($settings['frontend'])) {
                            $conditions['(*boolean)${CALLBACK.is_admin}'] = false;
                        }
                        if (!empty($settings['backend'])) {
                            $conditions['(*boolean)${CALLBACK.is_admin}'] = true;
                        }
                        if (!empty($settings['api'])) {
                            $conditions['(*boolean)${CONST.REST_REQUEST}'] = true;
                        }
                    }

                    if (!empty($conditions)) {
                        $item['Condition']['Equals'] = $conditions;
                    }

                    $tree->statements[] = $item;
                    break;

                case 'teaser':
                    $tree->statements[] = array(
                        'Effect'  => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Teaser' => array(
                                'Value' => esc_js($settings['message'])
                            )
                        )
                    );
                    break;

                case 'limited':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Limited' => array(
                                'Threshold' => intval($settings['threshold'])
                            )
                        )
                    );
                    break;

                case 'redirected':
                    $metadata = array(
                        'Type' => $settings['type'],
                        'Code' => intval(isset($settings['httpCode']) ? $settings['httpCode'] : 307)
                    );

                    if ($settings['type'] === 'page') {
                        $metadata['Id'] = intval($settings['destination']);
                    } elseif ($settings['type']  === 'url') {
                        $metadata['URL'] = trim($settings['destination']);
                    } elseif ($settings['type'] === 'callback') {
                        $metadata['Callback'] = trim($settings['destination']);
                    }

                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Redirect' => $metadata
                        )
                    );
                    break;

                case 'protected':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Metadata' => array(
                            'Password' => array(
                                'Value' => $settings['password']
                            )
                        )
                    );
                    break;

                case 'ceased':
                    $tree->statements[] = array(
                        'Effect'   => ($effect === 'denied' ? 'deny' : 'allow'),
                        'Action'   => 'Read',
                        'Resource' => $resource,
                        'Condition' => array(
                            'Greater' => array(
                                '(*int)${DATETIME.U}' => intval($settings['after'])
                            )
                        )
                    );
                    break;

                default:
                    do_action(
                        'aam_post_option_to_policy_action',
                        $resource,
                        $option,
                        $effect,
                        $settings,
                        $tree
                    );
                    break;
            }

            if ($action !== null) {
                if ($effect === 'allowed') {
                    $tree->allowed[] = $resource . ':' . $action;
                } else {
                    $tree->denied[] = $resource . ':' . $action;
                }
            }
        }

        // Finally prepare the consolidated statements
        if (!empty($tree->denied)) {
            $tree->statements[] = array(
                'Effect'   => 'deny',
                'Resource' => $tree->denied
            );
        }

        if (!empty($tree->allowed)) {
            $tree->statements[] = array(
                'Effect'   => 'allow',
                'Resource' => $tree->allowed
            );
        }

        return $tree->statements;
    }

    /**
     * Authorize RESTful action before it is dispatched by RESTful Server
     *
     * @param mixed  $response
     * @param object $handler
     * @param object $request
     *
     * @return mixed
     *
     * @since 6.6.1 https://github.com/aamplugin/advanced-access-manager/issues/137
     * @since 6.1.0 Fixed bug that causes fatal error when callback is Closure
     * @since 6.0.2 Making sure that get_post returns actual post object
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.1
     */
    public function beforeDispatch($response, $handler, $request)
    {
        // Register hooks that check post access
        foreach (get_post_types(array('show_in_rest' => true)) as $type) {
            add_filter("rest_prepare_{$type}", array($this, 'authPostAccess'), 10, 3);
        }

        // Override the password authentication handling ONLY for posts
        $attrs      = $request->get_attributes();
        $callback   = $attrs['callback'];
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
     * Check if post is allowed to be viewed
     *
     * @param WP_REST_Response $response
     * @param WP_Post          $post
     * @param WP_REST_Request  $request
     *
     * @access public
     * @version 6.0.0
     */
    public function authPostAccess($response, $post, $request)
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE,
            $post->ID
        );

        $auth = $this->isAuthorizedToReadPost(
            $object,
            (isset($request['_password']) ? $request['_password'] : null)
        );

        if (is_wp_error($auth)) {
            $data           = $auth->get_error_data();
            $data['reason'] = $auth->get_error_message();
            $data['code']   = $auth->get_error_code();
            $status         = $data['status'];

            unset($data['status']); // No need to duplicate the status code

            $response = new WP_REST_Response($data, $status);

            if ($auth->get_error_code() === 'post_access_redirected') {
                $response->set_headers(array('Location' => $data['url']));
            }
        } else {
            $this->incrementPostReadCounter($object);
        }

        return $response;
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
     * @access public
     * @version 6.0.0
     */
    public function isPasswordRequired($result, $post)
    {
        // Honor the manually set password on the post
        if (($result === false) && is_a($post, 'WP_Post')) {
            $check = $this->checkPostPassword(
                AAM::getUser()->getObject('post', $post->ID)
            );

            $result = is_wp_error($check);
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
     * @access public
     * @version 6.0.0
     */
    public function checkPassExpiration($expire)
    {
        $overwrite = AAM_Core_Config::get('feature.post.password.expires', null);

        if (!is_null($overwrite)) {
            $expire = ($overwrite ? time() + strtotime($overwrite) : 0);
        }

        return $expire;
    }

    /**
     * Filter Navigation menu
     *
     * @param array $pages
     *
     * @return array
     *
     * @since 6.2.0 Enhanced HIDDEN option to be more granular
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.0
     */
    public function getNavigationMenu($pages)
    {
        if (is_array($pages)) {
            foreach ($pages as $i => $page) {
                if (in_array($page->type, array('post_type', 'custom'), true)) {
                    $object = AAM::getUser()->getObject('post', $page->object_id);
                    if ($this->_isHidden($object->getOption())) {
                        unset($pages[$i]);
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Main frontend access control hook
     *
     * @return void
     *
     * @access public
     * @global WP_Query $wp_query
     * @version 6.0.0
     */
    public function wp()
    {
        global $wp_query;

        if ($wp_query->is_single || $wp_query->is_page) {
            $post = AAM_Core_API::getCurrentPost();

            if (is_a($post, 'AAM_Core_Object_Post')) {
                $error = $this->isAuthorizedToReadPost($post);

                if (is_wp_error($error)) {
                    if ($error->get_error_code() === 'post_access_redirected') {
                        AAM_Core_Redirect::execute('url', $error->get_error_data());
                    } elseif ($error->get_error_code() !== 'post_access_protected') {
                        wp_die($error->get_error_message(), 'aam_access_denied');
                    }
                } else {
                    $this->incrementPostReadCounter($post);
                }
            }
        }
    }

    /**
     * Filter posts from the list
     *
     * @param array $pages
     *
     * @return array
     *
     * @since 6.2.0 Enhanced HIDDEN option to be more granular
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.0
     */
    public function filterPages($pages)
    {
        if (is_array($pages)) {
            $current = AAM_Core_API::getCurrentPost();

            foreach ($pages as $i => $post) {
                if ($current && ($current->ID === $post->ID)) {
                    continue;
                }

                $object = AAM::getUser()->getObject('post', $post->ID);
                if ($this->_isHidden($object->getOption())) {
                    unset($pages[$i]);
                }
            }

            $pages = array_values($pages);
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
     * @since 6.0.4 Fixed incompatibility with some quite aggressive plugins that
     *              mutate global state of the WP_Query args
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.4
     */
    public function filterPostQuery($clauses, $wp_query)
    {
        static $executing = false;

        if (!$wp_query->is_singular && !$executing) {
            $executing = true;

            $object = AAM::getUser()->getObject(
                AAM_Core_Object_Visibility::OBJECT_TYPE
            );

            $query = $this->preparePostQuery($object->getSegment('post'), $wp_query);

            $clauses['where'] .= apply_filters(
                'aam_content_visibility_where_clause_filter',
                $query,
                $wp_query
            );

            $executing = false;
        }

        return $clauses;
    }

    /**
     * Prepare post query
     *
     * @param array    $visibility
     * @param WP_Query $wpQuery
     *
     * @return string
     *
     * @since 6.2.0 Enhanced HIDDEN option to be more granular
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @global WPDB $wpdb
     * @version 6.2.0
     */
    protected function preparePostQuery($visibility, $wpQuery)
    {
        global $wpdb;

        $postTypes = $this->getQueryingPostType($wpQuery);
        $excluded  = array();

        foreach ($visibility as $id => $access) {
            $chunks = explode('|', $id);

            if (in_array($chunks[1], $postTypes, true) && $this->_isHidden($access)) {
                $excluded[] = $chunks[0];
            }
        }

        if (!empty($excluded)) {
            $query = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $excluded) . ")";
        } else {
            $query = '';
        }

        return $query;
    }

    /**
     * Determine if object is hidden based on access settings
     *
     * @param array $options
     *
     * @return boolean
     *
     * @access private
     * @version 6.2.0
     */
    private function _isHidden($options)
    {
        $hidden = false;

        // Determine current area
        if (is_admin()) {
            $area = 'backend';
        } elseif (defined('REST_REQUEST') && REST_REQUEST) {
            $area = 'api';
        } else {
            $area = 'frontend';
        }

        if (isset($options['hidden'])) {
            if (
                is_array($options['hidden'])
                && !empty($options['hidden']['enabled'])
                && !empty($options['hidden'][$area])
            ) {
                $hidden = true;
            } elseif (is_bool($options['hidden']) && ($options['hidden'] === true)) {
                $hidden = true;
            }
        }

        return $hidden;
    }

    /**
     * Get querying post type
     *
     * @param WP_Query $wpQuery
     *
     * @return array
     *
     * @since 6.0.3 Fetch list of all possible post types
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.0.3
     */
    protected function getQueryingPostType($wpQuery)
    {
        if (!empty($wpQuery->query['post_type'])) {
            $postType = $wpQuery->query['post_type'];
        } elseif (!empty($wpQuery->query_vars['post_type'])) {
            $postType = $wpQuery->query_vars['post_type'];
        } elseif ($wpQuery->is_attachment) {
            $postType = 'attachment';
        } elseif ($wpQuery->is_page) {
            $postType = 'page';
        } else {
            $postType = 'any';
        }

        if ($postType === 'any') {
            $postType = array_keys(get_post_types(array(), 'names'));
        }

        return (array) $postType;
    }

    /**
     * Filter post content
     *
     * @param string $content
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function filterPostContent($content)
    {
        $post = AAM_Core_API::getCurrentPost();

        if (is_a($post, 'AAM_Core_Object_Post') && $post->has('teaser')) {
            $teaser = $post->get('teaser');

            if (!empty($teaser['message'])) {
                $message = $teaser['message'];
            } else {
                $message = __('[No teaser message provided]', AAM_KEY);
            }

            // Replace the [excerpt] placeholder with posts excerpt and do
            // short-code evaluation
            $content = do_shortcode(
                str_replace('[excerpt]', $post->post_excerpt, $message)
            );
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
     * @param int    $user_id
     * @param array  $args
     *
     * @return array
     *
     * @since 6.7.7 https://github.com/aamplugin/advanced-access-manager/issues/184
     * @since 6.1.0 Added internal cache to optimize performance for posts that no
     *              longer exist but still referenced one way or another
     * @since 6.0.2 Completely rewrote this method to fixed loop caused by mapped
     *              aam|... post type capability
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.7
     */
    public function filterMetaMaps($caps, $cap, $user_id, $args)
    {
        // Internal cache to optimize search for no longer existing posts
        static $post_cache = array();

        global $post;

        // For optimization reasons, check only caps that belong to registered post
        // types
        if (in_array($cap, $this->postTypeCaps, true)) {
            // Critical part of the implementation. We do not know ahead what
            // capability is responsible for what action when it comes to post types.
            if (isset($args[0]) && is_scalar($args[0])) {
                $objectId = intval($args[0]);
            } elseif (is_a($post, 'WP_Post')) {
                $objectId = $post->ID;
            } else {
                $objectId = null;
            }

            // If object ID is not empty, then, potentially we are checking for perms
            // to perform one of the action against a post
            if (!empty($objectId) && !in_array($objectId, $post_cache, true)) {
                $requested = get_post($objectId);

                if (is_a($requested, 'WP_Post')) {
                    $post_type = get_post_type_object($requested->post_type);

                    if (is_a($post_type, 'WP_Post_Type')) {
                        $caps = $this->__mapPostTypeCaps(
                            $post_type,
                            $cap,
                            $caps,
                            $requested,
                            $args
                        );
                    }
                } else {
                    $post_cache[] = $objectId;
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
     * @version 6.0.2
     */
    private function __mapPostTypeCaps(
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
            case 'edit_page':
                // Cover the scenario when user uses Bulk Action or Quick Edit to
                // change the Status to Published and post is not allowed to be
                // published
                $action = AAM_Core_Request::request('action');
                $status = AAM_Core_Request::request('_status');

                if (
                    in_array($action, array('edit', 'inline-save', true))
                    && $status === 'publish'
                ) {
                    $caps = $this->mapPublishPostCaps($caps, $post->ID);
                } else {
                    $caps = $this->mapEditPostCaps($caps, $post->ID);
                }
                break;

            case 'delete_post':
            case 'delete_page':
                $caps = $this->mapDeletePostCaps($caps, $post->ID);
                break;

            case 'read_post':
            case 'read_page':
                $password = (isset($args[1]) ? $args[1] : null);
                $caps     = $this->mapReadPostCaps($caps, $post->ID, $password);
                break;

            case 'publish_post':
            case 'publish_page':
            case 'publish_posts':
                $caps = $this->mapPublishPostCaps($caps, $post->ID);
                break;

            default:
                break;
        }

        return $caps;
    }

    /**
     * Mutate capability meta map based on ability to publish the post
     *
     * @param array       $caps
     * @param WP_Post|int $post
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function mapPublishPostCaps($caps, $post)
    {
        if ($this->isAuthorizedToPublishPost($post) === false) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Authorize the post publishing action
     *
     * @param WP_Post|int $post
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isAuthorizedToPublishPost($post)
    {
        return AAM::getUser()->getObject(
            'post',
            (is_a($post, 'WP_Post') ? $post->ID : $post)
        )->isAllowedTo('publish');
    }

    /**
     * Mutate capability meta map based on ability to edit/update the post
     *
     * @param array       $caps
     * @param WP_Post|int $post
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function mapEditPostCaps($caps, $post)
    {
        if ($this->isAuthorizedToEditPost($post) === false) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Check if current user is allowed to edit post
     *
     * Draft posts have to be omitted to avoid "egg-chicken" problem
     *
     * @param WP_Post|int $post
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isAuthorizedToEditPost($post)
    {
        $object  = AAM::getUser()->getObject(
            'post',
            (is_a($post, 'WP_Post') ? $post->ID : $post)
        );
        $isDraft = $object->post_status === 'auto-draft';

        return $isDraft || $object->isAllowedTo('edit');
    }

    /**
     * Mutate capability meta map based on ability to trash/delete the post
     *
     * @param array       $caps
     * @param WP_Post|int $post
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function mapDeletePostCaps($caps, $post)
    {
        if ($this->isAuthorizedToDeletePost($post) === false) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Check if current user is authorized to trash or permanently delete the post
     *
     * @param WP_Post|int $post
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isAuthorizedToDeletePost($post)
    {
        return AAM::getUser()->getObject(
            'post',
            (is_a($post, 'WP_Post') ? $post->ID : $post)
        )->isAllowedTo('delete');
    }

    /**
     * Mutate capability meta map based on ability to edit/update the post
     *
     * @param array       $caps
     * @param WP_Post|int $post
     * @param string      $password
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function mapReadPostCaps($caps, $post, $password = null)
    {
        if ($this->isAuthorizedToReadPost($post, $password) !== true) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Increment user view counter is tracking is defined
     *
     * @param AAM_Core_Object_Post $post
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function incrementPostReadCounter($post)
    {
        if (is_user_logged_in() && $post->is('limited')) {
            $option  = sprintf(self::POST_COUNTER_DB_OPTION, $post->ID);
            $counter = intval(get_user_option($option, get_current_user_id()));
            update_user_option(get_current_user_id(), $option, ++$counter);
        }
    }

    /**
     * Check if current user is authorized to read the post
     *
     * If post requires, password, also path this as the second optional parameter
     *
     * @param mixed  $post
     * @param string $password
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 6.0.0
     */
    public function isAuthorizedToReadPost($post, $password = null)
    {
        if (is_a($post, 'AAM_Core_Object_Post')) {
            $object = $post;
        } else {
            $object = AAM::getUser()->getObject(
                'post',
                (is_a($post, 'WP_Post') ? $post->ID : $post)
            );
        }

        // Prepare the pipeline of steps that AAM core will perform to check post's
        // accessibility
        $pipeline = apply_filters('aam_post_read_access_pipeline_filter', array(
            // Step #1. Check if access expired to the post
            array($this, 'checkPostExpiration'),
            // Step #2. Check if user has access to read the post
            array($this, 'checkPostReadAccess'),
            // Step #3. Check if counter exceeded max allowed views
            array($this, 'checkPostLimitCounter'),
            // Step #4. Check if redirect is defined for the post
            array($this, 'checkPostRedirect'),
            // Step #5. Check if post is password protected
            array($this, 'checkPostPassword')
        ));

        // Execute the collection of steps and stop when first restriction captured
        $result = true;
        foreach ($pipeline as $callback) {
            $result = call_user_func($callback, $object, $password);

            if (is_wp_error($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * Check CEASED access option
     *
     * If access is expired, return WP_Error object with the reason
     *
     * @param AAM_Core_Object_Post $post
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 6.0.0
     */
    public function checkPostExpiration(AAM_Core_Object_Post $post)
    {
        $result = true;

        if ($post->is('ceased')) {
            $ceased = $post->get('ceased');
            $now    = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();

            if ($ceased['after'] <= $now) {
                $result = new WP_Error(
                    'post_access_expired',
                    'User is unauthorized to access this post. Access Expired.',
                    array('status' => 401)
                );
            }
        }

        return $result;
    }

    /**
     * Check RESTRICTED options
     *
     * If access is explicitly restricted, return WP_Error object with the reason
     *
     * @param AAM_Core_Object_Post $post
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 6.0.0
     */
    public function checkPostReadAccess(AAM_Core_Object_Post $post)
    {
        $result = true;

        if ($post->is('restricted')) {
            $result = new WP_Error(
                'post_access_restricted',
                "User is unauthorized to access this post. Access denied.",
                array('status' => 401)
            );
        }

        return $result;
    }

    /**
     * Check LIMITED access option
     *
     * The counter is stored per each user for every individual post that has LIMITED
     * access option enabled. The WP_Error object will be returned if access counter
     * exceeded maximum allowed threshold.
     *
     * @param AAM_Core_Object_Post $post
     *
     * @return boolean|WP_Error
     *
     * @since 6.2.0 Simplified implementation
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.0
     */
    public function checkPostLimitCounter(AAM_Core_Object_Post $post)
    {
        $result = true;

        // Check current access counter only for authenticated users
        if (is_user_logged_in() && $post->is('limited')) {
            $limited = $post->get('limited');

            $option  = sprintf(self::POST_COUNTER_DB_OPTION, $post->ID);
            $counter = intval(get_user_option($option, get_current_user_id()));

            if ($counter >= $limited['threshold']) {
                $result = new WP_Error(
                    'post_access_exceeded_limit',
                    "User exceeded allowed access number. Access denied.",
                    array('status' => 401)
                );
            }
        }

        return $result;
    }

    /**
     * Check REDIRECTED access option
     *
     * Do not allow direct access to the post and return WP_Error object with details
     * for the location where user has to be redirected.
     *
     * @param AAM_Core_Object_Post $post
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 6.0.0
     */
    public function checkPostRedirect(AAM_Core_Object_Post $post)
    {
        $result = true;

        if ($post->is('redirected')) {
            $redirect  = $post->get('redirected');
            $location  = null;

            switch ($redirect['type']) {
                case 'page':
                    $location = get_page_link($redirect['destination']);
                    break;

                case 'login':
                    $location = add_query_arg(
                        'reason',
                        'restricted',
                        wp_login_url($this->getFromServer('REQUEST_URI'))
                    );
                    break;

                case 'url':
                    $location = $redirect['destination'];
                    break;

                case 'callback':
                    if (is_callable($redirect['destination'])) {
                        $location = call_user_func($redirect['destination'], $post);
                    } else {
                        _doing_it_wrong(
                            __CLASS__ . '::' . __METHOD__,
                            'Callback is not invocable',
                            AAM_VERSION
                        );
                    }
                    break;

                default:
                    break;
            }

            $result = new WP_Error(
                'post_access_redirected',
                'Direct access is not allowed. Follow the provided redirect rule.',
                array(
                    'url'    => $location,
                    'status' => $redirect['httpCode']
                )
            );
        }

        return $result;
    }

    /**
     * Check PASSWORD PROTECTED access option
     *
     * If post has password set, return WP_Error so the application can do further
     * authorization process.
     *
     * @param AAM_Core_Object_Post $post
     * @param string               $password
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 6.0.0
     */
    public function checkPostPassword(AAM_Core_Object_Post $post, $password = null)
    {
        $result = true;

        if ($post->is('protected')) {
            // Get password values
            $protected = $post->get('protected');

            // If password is empty or not provided, try to read it from the cookie.
            // This is the default WordPress behavior when it comes to password
            // protected posts/pages
            if (empty($password)) {
                $password = wp_unslash(
                    $this->getFromCookie('wp-postpass_' . COOKIEHASH)
                );

                $isMatched = AAM_Core_API::prepareHasher()->CheckPassword(
                    $protected['password'],
                    $password
                );
            } else {
                $isMatched = $protected['password'] === $password;
            }

            if ($isMatched === false) {
                $result = new WP_Error(
                    'post_access_protected',
                    'The post is password protected. Invalid password provided.',
                    array(
                        'status' => 401
                    )
                );
            }
        }

        return $result;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Content::bootstrap();
}