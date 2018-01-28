<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend authorization
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Authorization {

    /**
     * Instance of itself
     * 
     * @var AAM_Backend_Authorization
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //control admin area
        if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
            add_action('admin_init', array($this, 'checkScreenAccess'));
        }
        
        //additional filter for user capabilities
        add_filter('user_has_cap', array($this, 'isUserCapable'), 999, 3);
        
        //post access
        add_action('admin_action_edit', array($this, 'checkEditAccess'));
    }
    
    /**
     * Check screen access
     * 
     * @return void
     * 
     * @access public
     * @global string $plugin_page
     */
    public function checkScreenAccess() {
        global $plugin_page;
        
        //compile menu
        $menu = $plugin_page;
        
        if (empty($menu)){
            $menu     = basename(AAM_Core_Request::server('SCRIPT_NAME'));
            $taxonomy = AAM_Core_Request::get('taxonomy');
            $postType = AAM_Core_Request::get('post_type');
            $page     = AAM_Core_Request::get('page');
            
            if (!empty($taxonomy)) {
                $menu .= '?taxonomy=' . $taxonomy;
            } elseif (!empty($postType)) {
                $menu .= '?post_type=' . $postType;
            } elseif (!empty($page)) {
                $menu .= '?page=' . $page;
            }
        }
        
        if (AAM::getUser()->getObject('menu')->has($menu, true)) {
            AAM_Core_API::reject(
                'backend', array('hook' => 'access_backend_menu', 'id' => $menu)
            );
        }
    }
    
    /**
     * Check user capability
     * 
     * This is a hack function that add additional layout on top of WordPress
     * core functionality. Based on the capability passed in the $args array as
     * "0" element, it performs additional check on user's capability to manage
     * post.
     * 
     * @param array $allCaps
     * @param array $metaCaps
     * @param array $args
     * 
     * @return array
     * 
     * @access public
     */
    public function isUserCapable($allCaps, $metaCaps, $args) {
        global $post;
        
        //check if current user is allowed to edit or delete user
        if (in_array($args[0], array('edit_user', 'delete_user'))) {
            $allCaps = $this->isAllowedToManagerUser($args[0], $allCaps, $metaCaps);
        } elseif (isset($args[2]) && is_scalar($args[2])) { //make sure it is post ID
            $allCaps = $this->isAllowedToManagerPost(
                    $args[0], $args[2], $allCaps, $metaCaps
            );
        } elseif (is_a($post, 'WP_Post')) {
            if (in_array($args[0], array('publish_posts', 'publish_pages'))) {
                $object = AAM::getUser()->getObject('post', $post->ID);
                
                if (!$this->isAllowed('backend.publish', $object)) {
                     $allCaps = $this->restrictCapabilities($allCaps, $metaCaps);
                }
            }
        }
        
        return $allCaps;
    }
    
    /**
     * Control Edit Post
     *
     * Make sure that current user does not have access to edit Post
     *
     * @return void
     *
     * @access public
     */
    public function checkEditAccess() {
        $post = $this->getCurrentPost();
        
        if (is_a($post, 'WP_Post')) {
            $object = AAM::getUser()->getObject('post', $post->ID, $post);
            
            if (!$this->isAllowed('backend.edit', $object)) {
                AAM_Core_API::reject(
                    'backend', 
                    array(
                        'hook'   => 'post_edit', 
                        'action' => 'backend.edit', 
                        'post'   => $post
                    )
                );
            }
        }
    }
    
    /**
     * Get Post ID
     *
     * Replication of the same mechanism that is in wp-admin/post.php
     *
     * @return WP_Post|null
     *
     * @access public
     */
    protected function getCurrentPost() {
        $post = null;
        
        if (get_post()) {
            $post = get_post();
        } elseif ($post_id = AAM_Core_Request::get('post')) {
            $post = get_post($post_id);
        } elseif ($post_id = AAM_Core_Request::get('post_ID')) {
            $post = get_post($post_id);
        }

        return $post;
    }
    
    /**
     * Check if current user is allowed to manager specified user
     * 
     * @param int $id
     * @param array $allcaps
     * @param array $metacaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function isAllowedToManagerUser($id, $allcaps, $metacaps) {
        $user = new WP_User($id);
        
        //current user max level
        $cuserLevel = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        //userLevel
        $userLevel = AAM_Core_API::maxLevel($user->allcaps);

        if ($cuserLevel < $userLevel) {
            $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
        }
        
        return $allcaps;
    }
    
    /**
     * Check if current user is allowed to manage post
     * 
     * @param string $cap
     * @param int    $id
     * @param array  $allcaps
     * @param array  $metacaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function isAllowedToManagerPost($cap, $id, $allcaps, $metacaps) {
        if ($cap == 'edit_post') {
            $object = AAM::getUser()->getObject('post', $id);
            $draft  = $object->post_status == 'auto-draft';
            
            if (!$draft && !$this->isAllowed('backend.edit', $object)) {
                $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
            }
        } elseif ($cap == 'delete_post') {
            $object = AAM::getUser()->getObject('post', $id);
            if (!$this->isAllowed('backend.delete', $object)) {
                $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
            }
        }
        
        return $allcaps;
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
     * Restrict user capabilities
     * 
     * Iterate through the list of meta capabilities and disable them in the
     * list of all user capabilities. Keep in mind that this disable caps only
     * for one time call.
     * 
     * @param array $allCaps
     * @param array $metaCaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function restrictCapabilities($allCaps, $metaCaps) {
        foreach($metaCaps as $cap) {
            $allCaps[$cap] = false;
        }
        
        return $allCaps;
    }
    
    /**
     * Alias for the bootstrap
     * 
     * @return AAM_Backend_Authorization
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        return self::bootstrap();
    }
    
    /**
     * Bootstrap authorization layer
     * 
     * @return AAM_Backend_Authorization
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
}