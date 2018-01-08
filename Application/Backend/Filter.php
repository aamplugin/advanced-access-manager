<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Filter {

    /**
     * Instance of itself
     * 
     * @var AAM_Backend_Filter
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * pre_get_posts flag
     */
    protected $skip = false;

    /**
     * Initialize backend filters
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //menu filter
        add_filter('parent_file', array($this, 'filterMenu'), 999, 1);
        
        //manager WordPress metaboxes
        add_action("in_admin_header", array($this, 'metaboxes'), 999);
        
        //control admin area
        add_action('admin_notices', array($this, 'adminNotices'), -1);
        add_action('network_admin_notices', array($this, 'adminNotices'), -1);
        add_action('user_admin_notices', array($this, 'adminNotices'), -1);
        
        //admin bar
        add_action('wp_before_admin_bar_render', array($this, 'filterAdminBar'), 999);
        
        //post restrictions
        add_filter('page_row_actions', array($this, 'postRowActions'), 10, 2);
        add_filter('post_row_actions', array($this, 'postRowActions'), 10, 2);

        //default category filder
        add_filter('pre_option_default_category', array($this, 'filterDefaultCategory'));
        
        //add post filter for LIST restriction
        if (!AAM::isAAM() && AAM_Core_Config::get('check-post-visibility', true)) {
            add_filter('found_posts', array($this, 'filterPostCount'), 999, 2);
            add_filter('posts_fields_request', array($this, 'fieldsRequest'), 999, 2);
            add_action('pre_get_posts', array($this, 'preparePostQuery'), 999);
        }
        
        add_action('pre_post_update', array($this, 'prePostUpdate'), 10, 2);
        
        //user/role filters
        if (!is_multisite() || !is_super_admin()) {
            add_filter('editable_roles', array($this, 'filterRoles'));
            add_action('pre_get_users', array($this, 'filterUserQuery'), 999);
            add_filter('views_users', array($this, 'filterViews'));
        }
        
        AAM_Backend_Authorization::bootstrap(); //bootstrap backend authorization
    }
    
    /**
     * Filter the Admin Menu
     *
     * @param string $parent_file
     *
     * @return string
     *
     * @access public
     */
    public function filterMenu($parent_file) {
        //filter admin menu
        AAM::getUser()->getObject('menu')->filter();

        return $parent_file;
    }
    
    /**
     * Handle metabox initialization process
     *
     * @return void
     *
     * @access public
     */
    public function metaboxes() {
        global $post;

        //make sure that nobody is playing with screen options
        if (is_a($post, 'WP_Post')) {
            $screen = $post->post_type;
        } elseif ($screen_object = get_current_screen()) {
            $screen = $screen_object->id;
        } else {
            $screen = '';
        }

        if (AAM_Core_Request::get('init') != 'metabox') {
            AAM::getUser()->getObject('metabox')->filterBackend($screen);
        }
    }
    
    /**
     * Manage notifications visibility
     * 
     * @return void
     * 
     * @access public
     */
    public function adminNotices() {
        if (AAM_Core_API::capabilityExists('show_admin_notices')) {
            if (!AAM::getUser()->hasCapability('show_admin_notices')) {
                remove_all_actions('admin_notices');
                remove_all_actions('network_admin_notices');
                remove_all_actions('user_admin_notices');
            }
        }
    }
    
    /**
     * Filter top admin bar
     * 
     * The filter will be performed based on the Backend Menu access settings
     * 
     * @return void
     * 
     * @access public
     * @global WP_Admin_Bar $wp_admin_bar
     */
    public function filterAdminBar() {
        global $wp_admin_bar;
        
        $menu = AAM::getUser()->getObject('menu');
        foreach($wp_admin_bar->get_nodes() as $id => $node) {
            if (!empty($node->href)) {
                $suffix = str_replace(admin_url(), '', $node->href);
                if ($menu->has($suffix, true)) {
                    if (empty($node->parent) && $this->hasChildren($id)) { //root level
                        $node->href = '#';
                        $wp_admin_bar->add_node($node);
                    } else {
                        $wp_admin_bar->remove_menu($id);
                    }
                }
            }
        }
    }
    
    /**
     * Check if specified top bar item has children
     * 
     * @param string $id
     * 
     * @return boolean
     * 
     * @access protected
     * @global WP_Admin_Bar $wp_admin_bar
     */
    protected function hasChildren($id) {
        global $wp_admin_bar;
        
        $has = false;
        
        foreach($wp_admin_bar->get_nodes() as $node) {
            if ($node->parent == $id) {
                $has = true;
                break;
            }
        }
        
        return $has;
    }
    
    /**
     * Post Quick Menu Actions Filtering
     *
     * @param array   $actions
     * @param WP_Post $post
     *
     * @return array
     *
     * @access public
     */
    public function postRowActions($actions, $post) {
        $object = AAM::getUser()->getObject('post', $post->ID, $post);
        
        //filter edit menu
        if (!$this->isAllowed('backend.edit', $object)) {
            if (isset($actions['edit'])) { 
                unset($actions['edit']); 
            }
            if (isset($actions['inline hide-if-no-js'])) {
                unset($actions['inline hide-if-no-js']);
            }
        }
        
        //filter delete menu
        if (!$this->isAllowed('backend.delete', $object)) {
            if (isset($actions['trash'])) { unset($actions['trash']); }
            if (isset($actions['delete'])) { unset($actions['delete']); }
        }
        
        //filter edit menu
        if (!$this->isAllowed('backend.publish', $object)) {
            if (isset($actions['inline hide-if-no-js'])) {
                unset($actions['inline hide-if-no-js']);
            }
        }

        return $actions;
    }
    
    /**
     * Check if action is allowed
     * 
     * This method will take in consideration also *_others action
     * 
     * @param string               $action
     * @param AAM_Core_Object_Post $object
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isAllowed($action, $object) {
        $edit   = $object->has($action);
        $others = $object->has("{$action}_others");
        $author = ($object->post_author == get_current_user_id());
        
        return ($edit || ($others && !$author)) ? false : true;
    }
    
    /**
     * Override default category if defined
     * 
     * @param type $category
     * 
     * @return int
     * 
     * @access public
     * @staticvar type $default
     */
    public function filterDefaultCategory($category) {
        static $default = null;
        
        if (is_null($default)) {
            //check if user category is defined
            $id      = get_current_user_id();
            $default = AAM_Core_Config::get('default.category.user.' . $id , null);
            $roles   = AAM::getUser()->roles;
            
            if (is_null($default) && count($roles)) {
                $default = AAM_Core_Config::get(
                    'default.category.role.' . array_shift($roles), false
                );
            }
        }
        
        return ($default ? $default : $category);
    }
    
    /**
     * Filter post count for pagination
     *  
     * @param int      $counter
     * @param WP_Query $query
     * 
     * @return array
     * 
     * @access public
     */
    public function filterPostCount($counter, $query) {
        $filtered = array();
        
        foreach ($query->posts as $post) {
            if (isset($post->post_type)) {
                $type = $post->post_type;
            } else {
                $type = AAM_Core_API::getQueryPostType($query);
            }
            
            $object = (is_scalar($post) ? get_post($post) : $post);
            
            if (!AAM_Core_API::isHiddenPost($object, $type, 'backend')) {
                $filtered[] = $post;
            } else {
                $counter--;
                $query->post_count--;
            }
        }
        
        $query->posts = $filtered;

        return $counter;
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
        
        if ($qfields == 'id=>parent') {
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
     * Prepare pre post query
     * 
     * @param WP_Query $query
     * 
     * @return void
     * 
     * @access public
     */
    public function preparePostQuery($query) {
        if ($this->skip === false) {
            $this->skip = true;
            $filtered   = AAM_Core_API::getFilteredPostList($query, 'backend');
            $this->skip = false;
            
            if (isset($query->query_vars['post__not_in']) 
                    && is_array($query->query_vars['post__not_in'])) {
                $query->query_vars['post__not_in'] = array_merge(
                        $query->query_vars['post__not_in'], $filtered
                );
            } else {
                $query->query_vars['post__not_in'] = $filtered;
            }
        }
    }
    
    /**
     * Post update hook
     * 
     * Clear cache if post owner changed
     * 
     * @param int   $id
     * @param array $data
     * 
     * @return void
     * 
     * @access public
     */
    public function prePostUpdate($id, $data) {
        $post = get_post($id);
        
        if ($post->post_author != $data['post_author']) {
            AAM_Core_Cache::clear($id);
        }
    }
    
    /**
     * Filter roles
     * 
     * @param array $roles
     * 
     * @return array
     */
    public function filterRoles($roles) {
        $userLevel = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        
        //filter roles
        foreach($roles as $id => $role) {
            if (!empty($role['capabilities']) && is_array($role['capabilities'])) {
                $roleLevel = AAM_Core_API::maxLevel($role['capabilities']);
                if ($userLevel < $roleLevel) {
                    unset($roles[$id]);
                }
            }
        }
        
        return $roles;
    }
    
    /**
     * Filter user query
     * 
     * Exclude all users that have higher user level
     * 
     * @param object $query
     * 
     * @access public
     * 
     * @return void
     */
    public function filterUserQuery($query) {
        //current user max level
        $max     = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        $exclude = array();
        $roles   = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            if (AAM_Core_API::maxLevel($role->capabilities) > $max) {
                $exclude[] = $id;
            }
        }
        
        $query->query_vars['role__not_in'] = $exclude;
    }
    
    /**
     * Filter user list view options
     * 
     * @param array $views
     * 
     * @return array
     * 
     * @access public
     */
    public function filterViews($views) {
        $max   = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        $roles = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            if (isset($views[$id]) 
                    && AAM_Core_API::maxLevel($role->capabilities) > $max) {
                unset($views[$id]);
            }
        }
        
        return $views;
    }
    
    /**
     * Register backend filters and actions
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
    }

}