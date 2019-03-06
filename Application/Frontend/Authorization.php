<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM frontend authorization
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Frontend_Authorization {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Frontend_Authorization
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Check post access
     * 
     * Based on the provided post object, check if current user has access to it. 
     * This method run multiple checks at-once
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access public
     */
    public function checkReadAuth(AAM_Core_Object_Post $post) {
        // pre post access hook
        do_action('aam-pre-post-authorization-action', $post);
        
        // Step #1. Check if access expired to the post
        $this->checkExpiration($post);
        
        // Step #2. Check if user has access to read the post
        $this->checkReadAccess($post);
        
        // Step #3. Check if counter exceeded max allowed views
        $this->checkCounter($post);
        
        // Step #4. Check if redirect is defined for the post
        $this->checkRedirect($post);
        
        // post post access hook
        do_action('aam-post-post-authorization-action', $post);
    }
    
    /**
     * Check ACCESS_EXPIRATION option
     * 
     * If access is expired, override the access settings based on the
     * post.access.expired ConfigPress settings (default frontend.read)
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkExpiration($post) {
        $expire = $post->has('frontend.expire');

        if ($expire) {
            $date = strtotime($post->get('frontend.expire_datetime'));
            if ($date <= time()) {
                $actions = AAM_Core_Config::get(
                        'feature.frontend.postAccess.expired', 'frontend.read'
                );

                foreach(array_map('trim', explode(',', $actions)) as $action) {
                    $post->set($action, 1);
                }
            }
        }
    }
    
    /**
     * Check READ & READ_OTHERS options
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkReadAccess(AAM_Core_Object_Post $post) {
        if (!$post->allowed('frontend.read')) {
            $this->deny('post_read', 'frontend.read', $post->getPost());
        }
    }
    
    /**
     * Check ACCESS_COUNTER option
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkCounter(AAM_Core_Object_Post $post) {
        $user = get_current_user_id();
        
        //check counter only for authenticated users and if ACCESS COUNTER is set
        if ($user && $post->has('frontend.access_counter')) { 
            $counter = intval(get_user_meta(
                $user, 'aam-post-' . $post->ID . '-access-counter', true
            ));
            
            if ($counter >= $post->get('frontend.access_counter_limit')) {
                $this->deny('post_read', 'frontend.access_counter', $post->getPost());
            } else {
                update_user_meta(
                    $user, 'aam-post-' . $post->ID . '-access-counter', ++$counter
                );
            }
        }
    }
    
    /**
     * Check REDIRECT option
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkRedirect(AAM_Core_Object_Post $post) {
        if ($post->has(AAM_Core_Api_Area::get() . '.redirect')) {
            $rule = explode('|', $post->get(AAM_Core_Api_Area::get() . '.location'));
            $code = apply_filters('aam-post-redirect-http-code-filter', 307);
            
            if (count($rule) === 1) { // TODO: legacy. Remove in Jul 2020
                if ($rule[0] === 'login') {
                    AAM::api()->redirect('login');
                } else {
                    AAM_Core_API::redirect($rule[0]);
                }
            } elseif ($rule[0] === 'page') {
                wp_safe_redirect(get_page_link($rule[1]), $code); exit;
            } elseif ($rule[0] === 'url') {
                wp_redirect($rule[1], $code); exit;
            } elseif (($rule[0] === 'callback') && is_callable($rule[1])) {
                call_user_func($rule[1], $post);
            }
        }
    }
    
    /**
     * Deny access
     * 
     * @param string  $hook
     * @param string  $action
     * @param WP_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function deny($hook, $action, $post) {
        AAM_Core_API::reject('frontend', array(
            'hook' => $hook, 'action' => $action, 'post' => $post
        ));
    }
    
    /**
     * Alias for the bootstrap
     * 
     * @return AAM_Frontend_Authorization
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
     * @return void
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