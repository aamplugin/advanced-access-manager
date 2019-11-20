<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Posts & Terms service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Content
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

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
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
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
                    add_action('edit_category_form_fields', array($this, 'renderAccessTermMetabox'), 1);
                    add_action('edit_link_category_form_fields', array($this, 'renderAccessTermMetabox'), 1);
                    add_action('edit_tag_form_fields', array($this, 'renderAccessTermMetabox'), 1);

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
     * @since 6.0.1 Fixed bug related to enabling commenting on all posts
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.0.1
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

        // Working with post types
        add_action('registered_post_type', array($this, 'registerPostType'), 999, 2);

        // Check if user has ability to perform certain task based on provided
        // capability and meta data
        add_filter('map_meta_cap', array($this, 'filterMetaMaps'), 999, 4);

        // Get control over commenting stuff
        add_filter('comments_open', function($open, $id) {
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

        // Cover any kind of surprize things by other funky plugins
        add_filter('pre_update_option', array($this, 'updateOption'), 10, 2);
        add_filter('role_has_cap', array($this, 'roleHasCap'), 1, 3);
    }

    /**
     * Hook into option update process
     *
     * Filter out AAM dynamically modified post type capabilities before they get into
     * the database. Some plugins really like the idea to force custom capability
     * creation during CPT registration. Some themes cause even infinite loop if those
     * capabilities are not stored in the _user_roles option.
     *
     * @param mixed  $value
     * @param string $option
     *
     * @return mixed
     *
     * @access public
     * @global $wpdb
     * @version 6.0.0
     */
    public function updateOption($value, $option)
    {
        global $wpdb;

        if ($option === $wpdb->prefix . 'user_roles') {
            // Remove all pseudo capabilities from list of caps
            foreach ($value as &$role) {
                foreach ($role['capabilities'] as $cap => $granted) {
                    if (strpos($cap, 'aam|') === 0) {
                        $parts = explode('|', $cap);
                        unset($role['capabilities'][$cap]);
                        $role['capabilities'][$parts[2]] = $granted;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Hook into role has capability check
     *
     * @param array  $caps
     * @param string $cap
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function roleHasCap($caps, $cap)
    {
        if (strpos($cap, 'aam|') === 0) {
            $parts = explode('|', $cap);
            if (isset($caps[$parts[2]])) {
                $caps[$cap] = $caps[$parts[2]];
            }
        }

        return $caps;
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
     * @access public
     * @version 6.0.0
     */
    public function beforeDispatch($response, $handler, $request)
    {
        // Register hooks that check post access
        foreach(get_post_types(array('show_in_rest' => true)) as $type) {
            add_filter("rest_prepare_{$type}", array($this, 'authPostAccess'), 10, 3);
        }

        // Override the password authentication handling ONLY for posts
        $attrs    = $request->get_attributes();
        $callback = (!empty($attrs['callback'][0]) ? $attrs['callback'][0] : null);

        if (is_a($callback, 'WP_REST_Posts_Controller')) {
            $post = get_post($request['id']);

            // Honor the manually defined password on the post
            if (empty($post->post_password) && isset($request['password'])) {
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
            AAM_Core_Object_Post::OBJECT_TYPE, $post->ID
        );

        $auth = $this->isAuthorizedToReadPost(
            $object, (isset($request['_password']) ? $request['_password'] : null)
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
     * @access public
     * @version 6.0.0
     */
    public function getNavigationMenu($pages)
    {
        if (is_array($pages)) {
            foreach ($pages as $i => $page) {
                if (in_array($page->type, array('post_type', 'custom'), true)) {
                    $object = AAM::getUser()->getObject('post', $page->object_id);
                    if ($object->is('hidden')) {
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
     * @access public
     * @version 6.0.0
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
                if ($object->is('hidden')) {
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
     * @access public
     * @version 6.0.0
     */
    public function filterPostQuery($clauses, $wp_query)
    {
        if (!$wp_query->is_singular) {
            $object = AAM::getUser()->getObject(
                AAM_Core_Object_Visibility::OBJECT_TYPE
            );

            $query = $this->preparePostQuery($object->getSegment('post'), $wp_query);

            $clauses['where'] .= apply_filters(
                'aam_content_visibility_where_clause_filter', $query, $wp_query
            );
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
     * @access protected
     * @global WPDB $wpdb
     * @version 6.0.0
     */
    protected function preparePostQuery($visibility, $wpQuery)
    {
        global $wpdb;

        $postTypes = $this->getQueryingPostType($wpQuery);

        $not = array();

        foreach ($visibility as $id => $access) {
            $chunks = explode('|', $id);

            if (in_array($chunks[1], $postTypes, true) && !empty($access['hidden'])) {
                $not[] = $chunks[0];
            }
        }

        if (!empty($not)) {
            $query = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $not) . ")";
        } else {
            $query = '';
        }

        return $query;
    }

    /**
     * Get querying post type
     *
     * @param WP_Query $wpQuery
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
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
            $postType = array_keys(get_post_types(array('public' => true), 'names'));
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
     * Hook into post type registration process
     *
     * @param string       $type
     * @param WP_Post_Type $object
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function registerPostType($type, $object)
    {
        if (is_a($object, 'WP_Post_Type')) { // Work only with WP 4.6.0 or higher
            // The list of capabilities to override
            $override = array(
                'edit_post', 'delete_post', 'read_post', 'publish_posts'
            );

            foreach ($object->cap as $type => $capability) {
                if (in_array($type, $override, true)) {
                    $object->cap->{$type} = "aam|{$type}|{$capability}";
                }
            }
        }
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
     * @access public
     * @version 6.0.0
     */
    public function filterMetaMaps($caps, $cap, $user_id, $args)
    {
        global $post;

        $objectId = (isset($args[0]) ? $args[0] : null);

        // First of all delete all artificial capabilities from the $caps
        foreach ($caps as $i => $capability) {
            if (strpos($capability, 'aam|') === 0) {
                // Remove this capability from the mapped array and let WP Core
                // handle the correct mapping
                unset($caps[$i]);
            }
        }

        // This part needs to stay to cover scenarios where WP_Post_Type->cap->...
        // is not used but rather the hard-coded capability
        switch ($cap) {
            case 'edit_post':
                // Cover the scenario when user uses Bulk Action or Quick Edit to
                // change the Status to Published and post is not allowed to be
                // published
                $action = AAM_Core_Request::request('action');
                $status = AAM_Core_Request::request('_status');

                if (
                    in_array($action, array('edit', 'inline-save', true))
                    && $status === 'publish'
                ) {
                    $caps = $this->mapPublishPostCaps($caps, $objectId);
                } else {
                    $caps = $this->mapEditPostCaps($caps, $objectId);
                }
                break;

            case 'delete_post':
                $caps = $this->mapDeletePostCaps($caps, $objectId);
                break;

            case 'read_post':
                $caps = $this->mapReadPostCaps(
                    $caps, $objectId, (isset($args[1]) ? $args[1] : null)
                );
                break;


            case 'publish_post':
            case 'publish_posts':
            case 'publish_pages':
                // There is a bug in WP core that instead of checking if user has
                // ability to publish_post, it checks for edit_post. That is why
                // user has to be on the edit
                if (is_a($post, 'WP_Post')) {
                    $caps = $this->mapPublishPostCaps($caps, $post->ID);
                }
                break;

            default:
                if (strpos($cap, 'aam|') === 0) {
                    $caps = $this->checkPostTypePermission(
                        $caps, $cap, $user_id, $objectId
                    );
                }
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
            'post', (is_a($post, 'WP_Post') ? $post->ID : $post)
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
            'post', (is_a($post, 'WP_Post') ? $post->ID : $post)
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
            'post', (is_a($post, 'WP_Post') ? $post->ID : $post)
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
        if(is_user_logged_in() && $post->is('limited')) {
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
                'post', (is_a($post, 'WP_Post') ? $post->ID : $post)
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
     * @access public
     * @version 6.0.0
     */
    public function checkPostLimitCounter(AAM_Core_Object_Post $post)
    {
        $result = true;
        $user   = get_current_user_id();

        // Check current access counter only for authenticated users
        if ($user && $post->is('limited')) {
            $limited = $post->get('limited');
            $option  = sprintf(self::POST_COUNTER_DB_OPTION, $post->ID);
            $counter = intval(get_user_option($option, $user));

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
                    $protected['password'], $password
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

    /**
     * Check is user has capability attached to post type
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param int    $object
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function checkPostTypePermission($caps, $cap, $user_id, $object = null)
    {
        // Expecting to have:
        //   [0] === aam
        //   [1] === WP_Post_Type->cap key
        //   [2] === The capability
        $parts = explode('|', $cap);

        // Build the argument array for the current_user_can
        $args = array($parts[2]);
        if (!is_null($object)) {
            $args[] = $object;
        }

        if (call_user_func_array('current_user_can', $args)) {
            if ($parts[1] !== $parts[2]) {
                $caps = $this->filterMetaMaps(
                    $caps,
                    $parts[1],
                    $user_id,
                    array($object)
                );
            }
        } else {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Content::bootstrap();
}