<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM frontend filter
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Frontend_Filter {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Frontend_Filter
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
        //bootstrap authorization layer
        AAM_Frontend_Authorization::bootstrap();
        
        //manage access to frontend posts & pages
        add_action('wp', array($this, 'wp'), 999);
        add_action('404_template', array($this, 'themeRedirect'), 999);
        
        //important to keep this option optional for optimization reasons
        if (AAM_Core_Config::get('check-post-visibility', true)) {
            //filter navigation pages & taxonomies
            add_filter('get_pages', array($this, 'filterPostList'), 999);
            add_filter('wp_get_nav_menu_items', array($this, 'getNavigationMenu'), 999);

            //add post filter for LIST restriction
            add_filter('the_posts', array($this, 'filterPostList'), 999);
            add_action('pre_get_posts', array($this, 'preparePostQuery'), 999);
        }
        
        //password protected filter
        add_filter('post_password_required', array($this, 'isPassProtected'), 10, 2);
        //manage password check expiration
        add_filter('post_password_expires', array($this, 'checkPassExpiration'));
        
        //widget filters
        add_filter('sidebars_widgets', array($this, 'filterWidgets'), 999);
        
        //get control over commenting stuff
        add_filter('comments_open', array($this, 'commentOpen'), 10, 2);
        
        //filter post content
        add_filter('the_content', array($this, 'filterPostContent'), 999);
    }
    
    /**
     * Main frontend access control hook
     *
     * @return void
     *
     * @access public
     * @global WP_Post $post
     */
    public function wp() {
        global $wp_query;
        
        if ($wp_query->is_404) { // Handle 404 redirect
            $type = AAM_Core_Config::get('frontend.404redirect.type', 'default');
            do_action('aam-rejected-action', 'frontend', array(
                'hook' => 'aam_404', 
                'uri'  => AAM_Core_Request::server('REQUEST_URI')
            ));
            
            if ($type != 'default') {
                AAM_Core_API::redirect(
                    AAM_Core_Config::get("frontend.404redirect.{$type}")
                );
            }
        } elseif ($wp_query->is_single || $wp_query->is_page 
                                || $wp_query->is_posts_page || $wp_query->is_home) {
            $post = AAM_Core_API::getCurrentPost();
            
            if ($post) {
                AAM_Frontend_Authorization::getInstance()->post($post);
            }
        }
    }
    
    /**
     * Theme redirect
     * 
     * Super important function that cover the 404 redirect that triggered by theme
     * when page is not found. This covers the scenario when page is restricted from
     * listing and read.
     * 
     * @global type $wp_query
     * 
     * @param type $template
     * 
     * @return string
     * 
     * @access public
     */
    public function themeRedirect($template) {
        $post = AAM_Core_API::getCurrentPost();
        
        if ($post) {
            AAM_Frontend_Authorization::getInstance()->post($post);
        }
        
        return $template;
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
            foreach ($posts as $i => $post) {
                if ($current && ($current->ID == $post->ID)) { continue; }
                
                if (AAM_Core_API::isHiddenPost($post, $post->post_type)) {
                    unset($posts[$i]);
                }
            }
            
            $posts = array_values($posts);
        }
        
        return $posts;
    }
    
    /**
     * Filter Navigation menu
     *
     * @param array $pages
     *
     * @return array
     *
     * @access public
     */
    public function getNavigationMenu($pages) {
        if (is_array($pages)) {
            foreach ($pages as $i => $page) {
                if (in_array($page->type, array('post_type', 'custom'))) {
                    $post = get_post($page->object_id);
                    if (AAM_Core_API::isHiddenPost($post, $post->post_type)) {
                        unset($pages[$i]);
                    }
                }
            }
        }

        return $pages;
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
        
        if ($skip === false && !$this->isMainWP()) { // avoid loop
            $skip     = true;
            $filtered = AAM_Core_API::getFilteredPostList($query);
            $skip     = false;
            
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
     * Check if post is password protected
     * 
     * @param boolean $res
     * @param WP_Post $post
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isPassProtected($res, $post) {
        if (is_a($post, 'WP_Post')) {
            $object = AAM::getUser()->getObject('post', $post->ID);

            if ($object->has('frontend.protected')) {
                require_once( ABSPATH . 'wp-includes/class-phpass.php' );
                $hasher = new PasswordHash( 8, true );
                $pass   = $object->get('frontend.password');
                $hash   = wp_unslash(
                        AAM_Core_Request::cookie('wp-postpass_' . COOKIEHASH)
                );

                $res = empty($hash) ? true : !$hasher->CheckPassword($pass, $hash);
            }
        }
        
        return $res;
    }
    
    /**
     * Get password expiration TTL
     * 
     * @param int $expire
     * 
     * @return int
     * 
     * @access public
     */
    public function checkPassExpiration($expire) {
        $overwrite = AAM_Core_Config::get('post.password.expires', null);
        
        if ($overwrite !== null) {
            $expire = ($overwrite ? time() + strtotime($overwrite) : 0);
        }
        
        return $expire;
    }
    
    /**
     * Filter frontend widgets
     *
     * @param array $widgets
     *
     * @return array
     *
     * @access public
     */
    public function filterWidgets($widgets) {
        return AAM::getUser()->getObject('metabox')->filterFrontend($widgets);
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
        
        return ($object->has('frontend.comment') ? false : $open);
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
        
        if ($post && $post->has('frontend.limit')) {
            if ($post->has('frontend.teaser')) {
                $message = $post->get('frontend.teaser');
            } else {
                $message = __('[No teaser message provided]', AAM_KEY);
            }

            $content = do_shortcode(stripslashes($message));
        }
        
        return $content;
    }
    
    /**
     * Check if request comes from wp()
     * 
     * Super important method is used to solve the problem with hidden posts
     *
     * @return boolean
     * 
     * @access protected
     */
    protected function isMainWP() {
        $result = false;

        foreach(debug_backtrace() as $level) {
            $class = (isset($level['class']) ? $level['class'] : null);
            $func  = (isset($level['function']) ? $level['function'] : null);

            if ($class == 'WP' && $func == 'main') {
                $result = true;
                break;
            }
        }
        
        return $result;
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