<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shared manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Shared_Manager {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Shared_Manager
     * 
     * @access private 
     */
    private static $_instance = null;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $primitiveCaps = array(
        'edit_post', 'delete_post', 'read_post', 'publish_posts'
    );

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    protected $skipMetaCheck = false;

    /**
     * Constructor
     * 
     * @access protected
     * 
     * @return void
     */
    protected function __construct() {}
    
    /**
     * Initialize core hooks
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
            
            // Disable XML-RPC if needed
            if (!AAM_Core_Config::get('core.settings.xmlrpc', true)) {
                add_filter('xmlrpc_enabled', '__return_false');
            } else {
                add_action(
                    'xmlrpc_call', 
                    array(self::$_instance, 'authorizeXMLRPCRequest')
                );
            }

            // Disable RESTful API if needed
            if (!AAM_Core_Config::get('core.settings.restful', true)) {
                add_filter(
                    'rest_authentication_errors', 
                    array(self::$_instance, 'disableRest'), 
                    1
                );
            }
            
            //Register policy post type
            add_action('init', array(self::$_instance, 'init'));
            
            // Control post visibility
            add_filter(
                'posts_clauses_request', 
                array(self::$_instance, 'filterPostQuery'), 
                999, 
                2
            );

            //filter post content
            add_filter(
                'the_content', array(self::$_instance, 'filterPostContent'), 999
            );
            
            //filter admin toolbar
            if (AAM_Core_Config::get('core.settings.backendAccessControl', true)) {
                if (filter_input(INPUT_GET, 'init') !== 'toolbar') {
                    add_action(
                        'wp_before_admin_bar_render', 
                        array(self::$_instance, 'filterToolbar'), 
                        999
                    );
                }
            }

            // Working with post types
            add_action('registered_post_type', array(self::$_instance, 'registerPostType'), 999, 2);
            
            // Check if user has ability to perform certain task based on provided
            // capability and meta data
            add_filter('map_meta_cap', array(self::$_instance, 'mapMetaCaps'), 999, 4);
            
            // Security. Make sure that we escaping all translation strings
            add_filter(
                'gettext', array(self::$_instance, 'escapeTranslation'), 999, 3
            );

            //get control over commenting stuff
            add_filter('comments_open', array(self::$_instance, 'commentOpen'), 10, 2);
            
            // Role Manager. Tracking user role changes and if there is expiration
            // set, then trigger hooks
            add_action('add_user_role', array(self::$_instance, 'userRoleAdded'), 10, 2);
            add_action('remove_user_role', array(self::$_instance, 'userRoleRemoved'), 10, 2);
        }
        
        return self::$_instance;
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
     */
    public function registerPostType($type, $object) {
        if (is_a($object, 'WP_Post_Type')) { // Work only with WP 4.6.0 or higher
            foreach($object->cap as $type => $capability) {
                if (in_array($type, $this->primitiveCaps, true)) {
                    $object->cap->{$type} = "aam|{$type}|{$capability}";
                }
            }
        }
    }
    
    /**
     * 
     */
    public function init() {
        //check URI
        $this->checkURIAccess();
        
        //check Media Access if needed
        if (AAM_Core_Request::get('aam-media')) {
            AAM_Core_Media::bootstrap()->authorize();
        }
            
        //register CPT AAM_E_Product
        register_post_type('aam_policy', array(
            'label'        => __('Access Policy', AAM_KEY),
            'labels'       => array(
                'name' => __('Access Policies', AAM_KEY),
                'edit_item' => __('Edit Policy', AAM_KEY),
                'add_new_item' => __('Add New Policy', AAM_KEY),
                'new_item' => __('New Policy', AAM_KEY)
            ),
            'description'  => __('Access and security policy', AAM_KEY),
            'public'       => true,
            'show_ui'      => true,
            'show_in_menu' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'hierarchical' => false,
            'supports'     => array('title', 'excerpt', 'revisions'),
            'delete_with_user' => false,
            'capabilities' => array(
                'edit_post'         => 'aam_edit_policy',
                'read_post'         => 'aam_read_policy',
                'delete_post'       => 'aam_delete_policy',
                'delete_posts'      => 'aam_delete_policies',
                'edit_posts'        => 'aam_edit_policies',
                'edit_others_posts' => 'aam_edit_others_policies',
                'publish_posts'     => 'aam_publish_policies',
            )
        ));
    }
    
    /**
     * 
     */
    protected function checkURIAccess() {
        $uri    = wp_parse_url(AAM_Core_Request::server('REQUEST_URI'));
        $object = AAM::api()->getUser()->getObject('uri');
        $params = array();
        
        if (isset($uri['query'])) {
            parse_str($uri['query'], $params);
        }
        
        if ($match = $object->findMatch($uri['path'], $params)) {
            if ($match['type'] !== 'allow') {
                AAM::api()->redirect($match['type'], $match['action']);
            }
        }
    }
    
    /**
     * 
     * @param type $userId
     * @param type $role
     */
    public function userRoleAdded($userId, $role) {
        $user = new AAM_Core_Subject_User($userId);
        AAM_Core_API::clearCache($user);
    }
    
    /**
     * 
     * @param type $userId
     * @param type $role
     */
    public function userRoleRemoved($userId, $role) {
        $user = new AAM_Core_Subject_User($userId);
        AAM_Core_API::clearCache($user);
    }
    
    /**
     * 
     * @param type $translation
     * @param type $text
     * @param type $domain
     * @return type
     */
    public function escapeTranslation($translation, $text, $domain) {
        if ($domain === AAM_KEY) {
            $translation = esc_js($translation);
        }
        
        return $translation;
    }
    
    /**
     * 
     * @global type $wp_admin_bar
     */
    public function filterToolbar() {
        global $wp_admin_bar;
        
        $toolbar = AAM::api()->getUser()->getObject('toolbar');
        $nodes   = $wp_admin_bar->get_nodes();
        
        foreach((is_array($nodes) ? $nodes : array()) as $id => $node) {
            if ($toolbar->has($id, true)) {
                if (!empty($node->parent)) { // update parent node with # link
                    $parent = $wp_admin_bar->get_node($node->parent);
                    if ($parent && ($parent->href === $node->href)) {
                        $wp_admin_bar->add_node(array(
                            'id'   => $parent->id,
                            'href' => '#'
                        ));
                    }
                }
                $wp_admin_bar->remove_node($id);
            }
        }
    }
    
    /**
     * 
     * @param type $method
     */
    public function authorizeXMLRPCRequest($method) {
        $object = AAM::api()->getUser(get_current_user_id())->getObject('route');
        if ($object->has('xmlrpc', $method)) {
            AAM_Core_API::getXMLRPCServer()->error(
                401, 
                'Authorization Error. You are not authorized to perform this action'
            );
        }
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
     */
    public function filterPostQuery($clauses, $wpQuery) {
        if (!$wpQuery->is_singular && $this->isPostFilterEnabled()) {
            $option = AAM::getUser()->getObject('visibility', 0)->getOption();

            if (!empty($option['post'])) {
                $query = $this->preparePostQuery($option['post'], $wpQuery);
            } else {
                $query = '';
            }
            
            $clauses['where'] .= apply_filters(
                'aam-post-where-clause-filter', $query, $wpQuery, $option
            );
            
            $this->finalizePostQuery($clauses);
        }
        
        return $clauses;
    }
    
    /**
     * 
     * @return type
     */
    protected function isPostFilterEnabled() {
        if (AAM_Core_Api_Area::isBackend()) {
            $visibility = AAM_Core_Config::get('core.settings.backendAccessControl', true);
        } elseif (AAM_Core_Api_Area::isAPI()) {
            $visibility = AAM_Core_Config::get('core.settings.apiAccessControl', true);
        } else {
            $visibility = AAM_Core_Config::get('core.settings.frontendAccessControl', true);
        }
        
        return $visibility;
    }
    
    /**
     * Get querying post type
     * 
     * @param WP_Query $wpQuery
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getQueryingPostType($wpQuery) {
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
            $postType = array_keys(
                get_post_types(
                    array('public' => true, 'exclude_from_search' => false), 
                    'names'
                )
            );
        }
        
        return (array) $postType;
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
     */
    protected function preparePostQuery($visibility, $wpQuery) {
        global $wpdb;
        
        $postTypes = $this->getQueryingPostType($wpQuery);
        
        $not = array();
        $area = AAM_Core_Api_Area::get();

        foreach($visibility as $id => $access) {
            $chunks = explode('|', $id);

            if (in_array($chunks[1], $postTypes, true)) {
                if (!empty($access["{$area}.list"])) {
                    $not[] = $chunks[0];
                }
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
     * Finalize post query
     * 
     * @param array &$clauses
     * 
     * @access protected
     * @global WPDB $wpdb
     */
    protected function finalizePostQuery(&$clauses) {
        global $wpdb;
        
        $table = $wpdb->term_relationships;
        
        if (strpos($clauses['where'], $table) !== false) {
            if (strpos($clauses['join'], $table) === false) {
                $clauses['join'] .= " LEFT JOIN {$table} ON ";
                $clauses['join'] .= "({$wpdb->posts}.ID = {$table}.object_id)";
            }
            
            if (empty($clauses['groupby'])) {
                $clauses['groupby'] = "{$wpdb->posts}.ID";
            }
        }
    }
    
    /**
     * Disable REST API
     * 
     * @param WP_Error|null|bool $response
     * 
     * @return \WP_Error
     * 
     * @access public
     */
    public function disableRest($response) {
        if (!is_wp_error($response)) {
            $response = new WP_Error(
                'rest_access_disabled', 
                __('RESTful API is disabled', AAM_KEY),
                array('status' => 403)
            );
        }
        
        return $response;
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
     */
    public function mapMetaCaps($caps, $cap, $user_id, $args) {
        global $post, $screen;

        $objectId = (isset($args[0]) ? $args[0] : null);

        // First of all delete all artificial capability from the $caps
        foreach($caps as $i => $capability) {
            if (strpos($capability, 'aam|') === 0) {
                $parts    = explode('|', $capability);
                $capability = $parts[2];
                $primitive  = $parts[1];
            } else {
                $primitive  = null;
            }

            if (in_array($capability, AAM_Backend_Feature_Main_Capability::$groups['aam'], true)) {
                if (!AAM_Core_API::capabilityExists($capability)) {
                    $capability = AAM_Core_Config::get(
                        'page.capability', 'administrator'
                    );
                }
            }

            // If capability is primitive - then do not include it in the list of meta caps
            if (in_array($primitive, $this->primitiveCaps, true)) {
                unset($caps[$i]);
            } else {
                $caps[$i] = $capability;
            }
        }

        switch($cap) {
            case 'edit_user':
            case 'delete_user':
                // Some plugins or themes simply do not provide the the user ID for
                // these capabilities. I did not find in WP core any place were they
                // violate this rule
                if (!empty($objectId)) {
                    $caps = $this->authorizeUserUpdate($caps, $objectId);
                }
                break;
            
            case 'install_plugins':
            case 'delete_plugins':
            case 'edit_plugins':
            case 'update_plugins':
                $action = explode('_', $cap);
                $caps   = $this->checkPluginsAction($action[0], $caps, $cap);
                break;
            
            case 'activate_plugin':
            case 'deactivate_plugin':
                $action = explode('_', $cap);
                $caps   = $this->checkPluginAction($objectId, $action[0], $caps, $cap);
                break;

            // This part needs to stay to cover scenarios where WP_Post_Type->cap->...
            // is not used but rather the hardcoded capability 
            case 'edit_post':
                $caps = $this->authorizePostEdit($caps, $objectId);
                break;
        
            case 'delete_post':
                $caps = $this->authorizePostDelete($caps, $objectId);
                break;
        
            case 'read_post':
                $caps = $this->authorizePostRead($caps, $objectId);
                break;
        
            
            case 'publish_post':
            case 'publish_posts':
            case 'publish_pages':
                // There is a bug in WP core that instead of checking if user has
                // ability to publish_post, it checks for edit_post. That is why
                // user has to be on the edit
                if (is_a($post, 'WP_Post')) {
                    $caps = $this->authorizePublishPost($caps, $post->ID);
                }
                break;
            
            default:
                if (strpos($cap, 'aam|') === 0) {
                    if (!$this->skipMetaCheck) {
                        $this->skipMetaCheck = true;
                        $caps = $this->checkPostTypePermission($caps, $cap, $objectId);
                        $this->skipMetaCheck = false;
                    }
                } else {
                    $caps = apply_filters('aam-map-meta-caps-filter', $caps, $cap, $args);
                }
                break;
        }
        
        return $caps;
    }

    /**
     * Check Post Permissions
     *
     * @param [type] $caps
     * @param [type] $cap
     * @param [type] $id
     * 
     * @return array
     * 
     * @access protected
     */
    protected function checkPostTypePermission($caps, $cap, $object = null) {
        global $post;

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
                switch($parts[1]) {
                    case 'edit_post':
                        $caps = $this->authorizePostEdit($caps, $object);
                        break;

                    case 'read_post':
                        $caps = $this->authorizePostRead($caps, $object);
                        break;

                    case 'delete_post':
                        $caps = $this->authorizePostDelete($caps, $object);
                        break;

                    case 'publish_posts':
                        // $post->ID is mandatory as 'publish_post' does not pass the
                        // current post
                        if (is_a($post, 'WP_Post')) {
                            $caps = $this->authorizePublishPost($caps, $post->ID);
                        }
                        break;

                    default:
                        break;
                }
            }
        } else {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * 
     * @param type $action
     * @param type $caps
     * @param type $cap
     * @return type
     */
    protected function checkPluginsAction($action, $caps, $cap) {
        $allow = AAM::api()->getPolicyManager()->isAllowed("Plugin:WP:{$action}");
        
        if ($allow !== null) {
            $caps[] = $allow ? $cap : 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * 
     * @param type $plugin
     * @param type $action
     * @param type $caps
     * @param type $cap
     * @return type
     */
    protected function checkPluginAction($plugin, $action, $caps, $cap) {
        $parts = explode('/', $plugin);
        $slug  = (!empty($parts[0]) ? $parts[0] : null);

        $allow = AAM::api()->getPolicyManager()->isAllowed("Plugin:{$slug}:WP:{$action}");
        if ($allow !== null) {
            $caps[] = $allow ? $cap : 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * Filter pages fields
     * 
     * @param string   $fields
     * @param WP_Query $query
     * 
     * @return string
     * 
     * @access public
     * @global WPDB $wpdb
     */
    public function fieldsRequest($fields, $query) {
        global $wpdb;
        
        $qfields = (isset($query->query['fields']) ? $query->query['fields'] : '');
        
        if ($qfields === 'id=>parent') {
            $author = "{$wpdb->posts}.post_author";
            if (strpos($fields, $author) === false) {
                $fields .= ", $author"; 
            }
            
            $status = "{$wpdb->posts}.post_status";
            if (strpos($fields, $status) === false) {
                $fields .= ", $status"; 
            }
                    
            $type = "{$wpdb->posts}.post_type";
            if (strpos($fields, $type) === false) {
                $fields .= ", $type"; 
            }        
        }
        
        return $fields;
    }
    
    /**
     * Filter post content
     * 
     * @param string $content
     * 
     * @return string
     * 
     * @access public
     * @global WP_Post $post
     */
    public function filterPostContent($content) {
        $post = AAM_Core_API::getCurrentPost();
        $area = AAM_Core_Api_Area::get();
        
        if ($post && $post->has($area . '.limit')) {
            if ($post->has($area . '.teaser')) {
                $message = $post->get($area . '.teaser');
            } else {
                $message = __('[No teaser message provided]', AAM_KEY);
            }

            $content = do_shortcode(stripslashes($message));
        }
        
        return $content;
    }
    
    /**
     * Check if current user is allowed to manager specified user
     * 
     * @param array $caps
     * @param int   $userId
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizeUserUpdate($caps, $userId) {
        $user = AAM::api()->getUser($userId);
        
        //current user max level
        $maxLevel  = AAM::getUser()->getMaxLevel();
        //userLevel
        $userLevel = AAM_Core_API::maxLevel($user->allcaps);

        if ($maxLevel < $userLevel) {
            $caps[] = 'do_not_allow';
        }
        
        return $caps;
    }

    /**
     * Control frontend commenting feature
     *
     * @param boolean $open
     * @param int     $post_id
     *
     * @return boolean
     *
     * @access public
     */
    public function commentOpen($open, $post_id) {
        $object = AAM::getUser()->getObject('post', $post_id);
        $area   = AAM_Core_Api_Area::get();

        return ($object->has($area . '.comment') ? false : $open);
    }
    
    /**
     * Check if current user is allowed to edit post
     * 
     * @param array $caps
     * @param int   $obj
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizePostEdit($caps, $obj) {
        $id     = (is_a($obj, 'WP_Post') ? $obj->ID : $obj);
        $object = AAM::getUser()->getObject('post', $id);
        $draft  = $object->post_status === 'auto-draft';
        $area   = AAM_Core_Api_Area::get();

        if (!$draft && (!$object->allowed($area . '.edit') )) {
            $caps[] = 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * Check if current user is allowed to delete post
     * 
     * @param array $caps
     * @param int   $id
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizePostDelete($caps, $obj) {
        $id     = (is_a($obj, 'WP_Post') ? $obj->ID : $obj);
        $object = AAM::getUser()->getObject('post', $id);
        $area   = AAM_Core_Api_Area::get();
        
        if (!$object->allowed($area . '.delete')) {
            $caps[] = 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * Check if user is allowed to publish post
     * 
     * @param array $caps
     * @param int   $id
     * 
     * @return array
     * 
     * @access protected
     * @global WP_Post $post
     */
    protected function authorizePublishPost($caps, $obj) {
        $id     = (is_a($obj, 'WP_Post') ? $obj->ID : $obj);
        $object = AAM::getUser()->getObject('post', $id);
        $area   = AAM_Core_Api_Area::get();
        
        if (!$object->allowed($area . '.publish')) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }
    
    /**
     * Check if user is allowed to publish post
     * 
     * @param array $caps
     * @param int   $id
     * 
     * @return array
     * 
     * @access protected
     * @global WP_Post $post
     */
    protected function authorizePostRead($caps, $obj) {
        $id     = (is_a($obj, 'WP_Post') ? $obj->ID : $obj);
        $object = AAM::getUser()->getObject('post', $obj);
        $area   = AAM_Core_Api_Area::get();

        if (!$object->allowed($area . '.read')) {
            $caps[] = 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * Get single instance of itself
     * 
     * @return AAM_Shared_Manager
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = self::bootstrap();
        }
        
        return self::$_instance;
    }
    
}