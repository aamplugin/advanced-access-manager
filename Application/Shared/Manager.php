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
            }

            // Disable RESTful API if needed
            if (!AAM_Core_Config::get('core.settings.restful', true)) {
                add_filter(
                    'rest_authentication_errors', 
                    array(self::$_instance, 'disableRest'), 
                    1
                );
            }

            // Control post visibility
            //important to keep this option optional for optimization reasons
            if (AAM_Core_Config::get('core.settings.checkPostVisibility', true)) {
                // filter navigation pages & taxonomies
                add_filter('get_pages', array(self::$_instance, 'filterPostList'), 999);
                // add post filter for LIST restriction
                add_filter('the_posts', array(self::$_instance, 'filterPostList'), 999);
                // pre post query builder
                add_action('pre_get_posts', array(self::$_instance, 'preparePostQuery'), 999);
            }
            
            //filter post content
            add_filter('the_content', array(self::$_instance, 'filterPostContent'), 999);
        }
        
        return self::$_instance;
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
     * @param array $caps
     * @param array $meta
     * @param array $args
     * 
     * @return array
     * 
     * @access public
     */
    public function userHasCap($caps, $meta, $args) {
        $capability = (isset($args[0]) && is_string($args[0]) ? $args[0] : '');
        $uid        = (isset($args[2]) && is_numeric($args[2]) ? $args[2] : 0);
        
        switch($capability) {
            case 'edit_user':
            case 'delete_user':
                $caps = $this->authorizeUserUpdate($uid, $caps, $meta);
                break;
            
            case 'edit_post':
                $caps = $this->authorizePostEdit($uid, $caps, $meta);
                break;
            
            case 'delete_post':
                $caps = $this->authorizePostDelete($uid, $caps, $meta);
                break;
            
            case 'publish_posts':
            case 'publish_pages':
                $caps = $this->authorizePublishPost($caps, $meta);
                break;
            
            default:
                break;
        }
        
        return $caps;
    }
    
    /**
     * Filter posts from the list
     *  
     * @param array $posts
     * 
     * @return array
     * 
     * @access public
     */
    public function filterPostList($posts) {
        $current = AAM_Core_API::getCurrentPost();
        
        if (is_array($posts)) {
            $area = AAM_Core_Api_Area::get();
            
            foreach ($posts as $i => $post) {
                if ($current && ($current->ID == $post->ID)) { continue; }
                
                // TODO: refactor this to AAM API standalone
                $object = AAM::getUser()->getObject('post', $post->ID);
                $hidden = $object->get($area. '.hidden');
                $list   = $object->get($area. '.list');
                
                if ($hidden || $list) {
                    unset($posts[$i]);
                }
            }
            
            $posts = array_values($posts);
        }
        
        return $posts;
    }
    
    /**
     * Build pre-post query request
     * 
     * This is used to solve the problem or pagination
     * 
     * @param stdClass $query
     * 
     * @return void
     * 
     * @access public
     */
    public function preparePostQuery($query) {
        static $skip = false;
        
        if ($skip === false) { // avoid loop
            $skip = true;
            // TODO: refactor this to AAM API standalone
            $filtered = AAM_Core_API::getFilteredPostList($query);
            $skip = false;
            
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
     * @param int   $id
     * @param array $allcaps
     * @param array $metacaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizeUserUpdate($id, $allcaps, $metacaps) {
        $user = new WP_User($id);
        
        //current user max level
        $maxLevel  = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        //userLevel
        $userLevel = AAM_Core_API::maxLevel($user->allcaps);

        if ($maxLevel < $userLevel) {
            $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
        }
        
        return $allcaps;
    }
    
    /**
     * Check if current user is allowed to edit post
     * 
     * @param int    $id
     * @param array  $allcaps
     * @param array  $metacaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizePostEdit($id, $allcaps, $metacaps) {
        $object = AAM::getUser()->getObject('post', $id);
        $draft  = $object->post_status == 'auto-draft';
        $area   = AAM_Core_Api_Area::get();

        if (!$draft && !$this->isActionAllowed($area . '.edit', $object)) {
            $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
        }
        
        return $allcaps;
    }
    
    /**
     * Check if current user is allowed to delete post
     * 
     * @param int    $id
     * @param array  $allcaps
     * @param array  $metacaps
     * 
     * @return array
     * 
     * @access protected
     */
    protected function authorizePostDelete($id, $allcaps, $metacaps) {
        $object = AAM::getUser()->getObject('post', $id);
        $area   = AAM_Core_Api_Area::get();
        
        if (!$this->isActionAllowed($area . '.delete', $object)) {
            $allcaps = $this->restrictCapabilities($allcaps, $metacaps);
        }
        
        return $allcaps;
    }
    
    /**
     * Check if user is allowed to publish post
     * 
     * @param array $allcaps
     * @param array $metacaps
     * 
     * @return array
     * 
     * @access protected
     * @global WP_Post $post
     */
    protected function authorizePublishPost($allcaps, $metacaps) {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            $object = AAM::getUser()->getObject('post', $post->ID);
            $area   = AAM_Core_Api_Area::get();

            if (!$this->isActionAllowed($area . '.publish', $object)) {
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
    protected function isActionAllowed($action, $object) {
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
     * 
     * @return type
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = self::bootstrap();
        }
        
        return self::$_instance;
    }
}