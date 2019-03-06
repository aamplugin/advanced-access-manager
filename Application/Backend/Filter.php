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
     * Initialize backend filters
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //menu filter
        if (!AAM::isAAM() || !current_user_can('aam_manage_admin_menu')) {
            add_filter('parent_file', array($this, 'filterMenu'), 999, 1);
        }
        
        //manager WordPress metaboxes
        add_action("in_admin_header", array($this, 'metaboxes'), 999);
        add_action("widgets_admin_page", array($this, 'metaboxes'), 999);
        
        //control admin area
        add_action('admin_notices', array($this, 'adminNotices'), -1);
        add_action('network_admin_notices', array($this, 'adminNotices'), -1);
        add_action('user_admin_notices', array($this, 'adminNotices'), -1);
        
        //post restrictions
        add_filter('page_row_actions', array($this, 'postRowActions'), 10, 2);
        add_filter('post_row_actions', array($this, 'postRowActions'), 10, 2);

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
        } else {
            $screen_object = get_current_screen();
            $screen        = ($screen_object ? $screen_object->id : '');
        }
        
        if (AAM_Core_Request::get('init') !== 'metabox') {
            if ($screen !== 'widgets') {
                AAM::getUser()->getObject('metabox')->filterBackend($screen);
            } else {
                AAM::getUser()->getObject('metabox')->filterAppearanceWidgets();
            }
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
            if (!current_user_can('show_admin_notices')) {
                remove_all_actions('admin_notices');
                remove_all_actions('network_admin_notices');
                remove_all_actions('user_admin_notices');
            }
        }
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
        if (!$object->allowed('backend.edit')) {
            if (isset($actions['edit'])) { 
                unset($actions['edit']); 
            }
            if (isset($actions['inline hide-if-no-js'])) {
                unset($actions['inline hide-if-no-js']);
            }
        }
        
        //filter delete menu
        if (!$object->allowed('backend.delete')) {
            if (isset($actions['trash'])) { unset($actions['trash']); }
            if (isset($actions['delete'])) { unset($actions['delete']); }
        }
        
        //filter edit menu
        if (!$object->allowed('backend.publish')) {
            if (isset($actions['inline hide-if-no-js'])) {
                unset($actions['inline hide-if-no-js']);
            }
        }

        return $actions;
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
        
        if (intval($post->post_author) !== intval($data['post_author'])) {
            AAM_Core_API::clearCache();
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
        static $levels = array(); // to speed-up the execution
        
        $userLevel = AAM::getUser()->getMaxLevel();
        
        //filter roles
        foreach($roles as $id => $role) {
            if (!empty($role['capabilities']) && is_array($role['capabilities'])) {
                if (!isset($levels[$id])) {
                    $levels[$id] = AAM_Core_API::maxLevel($role['capabilities']);
                }
                if ($userLevel < $levels[$id]) {
                    unset($roles[$id]);
                } elseif ($userLevel === $levels[$id] && $this->filterSameLevel()) {
                    unset($roles[$id]);
                }
            }
        }
        
        return $roles;
    }
    
    /**
     * 
     * @return type
     */
    protected function filterSameLevel() {
        $response = false;
        
        if (AAM_Core_API::capabilityExists('manage_same_user_level')) {
            $response = !current_user_can('manage_same_user_level');
        }
        
        return $response;
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
        $max     = AAM::getUser()->getMaxLevel();
        $exclude = array();
        $roles   = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            $roleMax = AAM_Core_API::maxLevel($role->capabilities);
            if ($roleMax > $max ) {
                $exclude[] = $id;
            } elseif ($roleMax === $max && $this->filterSameLevel()) {
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
        $max   = AAM::getUser()->getMaxLevel();
        $roles = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            $roleMax = AAM_Core_API::maxLevel($role->capabilities);
            if (isset($views[$id])) {
                if ($roleMax > $max) {
                    unset($views[$id]);
                } elseif ($roleMax === $max && $this->filterSameLevel()) {
                    unset($views[$id]);
                }
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