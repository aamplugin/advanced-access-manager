<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM RESTful Posts Resource
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @todo Rethink about DRY approach to the post access control
 */
class AAM_Api_Rest_Resource_Post {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Api_Rest_Resource_Post
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Authorize Post actions
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_Error|null
     * 
     * @access public
     */
    public function authorize($request) {
        $result = null;
        
        if ($request['id']) {
            $post = AAM::getUser()->getObject('post', $request['id']);

            switch($request->get_method()) {
                case 'GET':
                    $result = $this->authorizeRead($post, $request);
                    break;

                case 'POST':
                case 'PUT':
                case 'PATCH':
                    if ($request['status'] === 'publish') {
                        $result = $this->authorizePublish($post);
                    } else {
                        $result = $this->authorizeUpdate($post);
                    }
                    break;

                case 'DELETE':
                    $result = $this->authorizeDelete($post);
                    break;

                default:
                    break;
            }
        }
        
        return $result;
    }
    
    /**
     * Check post access
     * 
     * Based on the provided post object, check if current user has access to it. 
     * This method run multiple checks at-once
     * 
     * @param AAM_Core_Object_Post $post
     * @param WP_REST_Request      $request
     * 
     * @return void
     * 
     * @access protected
     */
    protected function authorizeRead(AAM_Core_Object_Post $post, $request) {
        $steps = array(
            // Step #1. Check if access expired to the post
            array($this, 'checkExpiration'),
            // Step #2. Check if user has access to read the post
            array($this, 'checkReadAccess'),
            // Step #3. Check if counter exceeded max allowed views
            array($this, 'checkCounter'),
            // Step #4. Check if redirect is defined for the post
            array($this, 'checkRedirect'),
            // Step #5. Check if post is password protected
            array($this, 'checkPassword')
        );
        
        return $this->processPipeline($steps, $post, $request);
    }
    
    /**
     * 
     * @param AAM_Core_Object_Post $post
     * @return type
     */
    protected function authorizePublish(AAM_Core_Object_Post $post) {
        $steps = array(
            // Step #1. Check if publish action is allowed
            array($this, 'checkPublish'),
        );
        
        return $this->processPipeline($steps, $post);
    }
    
    /**
     * 
     * @param AAM_Core_Object_Post $post
     * @return type
     */
    protected function authorizeUpdate(AAM_Core_Object_Post $post) {
        $steps = array(
            // Step #1. Check if edit action is allowed
            array($this, 'checkUpdate'),
        );
        
        return $this->processPipeline($steps, $post);
    }
    
    /**
     * 
     * @param AAM_Core_Object_Post $post
     * @return type
     */
    protected function authorizeDelete(AAM_Core_Object_Post $post) {
        $steps = array(
            // Step #1. Check if edit action is allowed
            array($this, 'checkDelete'),
        );
        
        return $this->processPipeline($steps, $post);
    }
    
    /**
     * 
     * @param array $pipeline
     * @param type $post
     * @param type $request
     * @return type
     */
    protected function processPipeline(array $pipeline, $post, $request = null) {
        foreach($pipeline as $callback) {
            $result = call_user_func_array($callback, array($post, $request));

            if (is_wp_error($result)) { break; }
        }
        
        return $result;
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
        $expire = $post->has('api.expire');

        if ($expire) {
            $date = strtotime($post->get('api.expire_datetime'));
            if ($date <= time()) {
                $actions = AAM_Core_Config::get(
                        'feature.api.postAccess.expired', 'api.read'
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
        $result = null;
        
        if (!$post->allowed('api.read')) {
            $result = new WP_Error(
                'rest_post_cannot_read', 
                "User is unauthorized to read the post. Access denied.", 
                array(
                    'action' => 'api.read',
                    'status' => 401
                )
            );
        }
        
        return $result;
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
        $result = null;
        $user   = get_current_user_id();
        
        //check counter only for authenticated users and if ACCESS COUNTER is set
        if ($user && $post->has('api.access_counter')) {
            $option  = 'aam-post-api-' . $post->ID . '-access-counter';
            $counter = intval(get_user_meta($user, $option, true));
            
            if ($counter >= $post->get('api.access_counter_limit')) {
                $result = new WP_Error(
                    'rest_post_cannot_read', 
                    "User exceeded allowed read number. Access denied.", 
                    array(
                        'action' => 'api.access_counter',
                        'status' => 401
                    )
                );
            } else {
                update_user_meta($user, $option, ++$counter);
            }
        }
        
        return $result;
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
        $result = null;
        
        if ($post->has('api.redirect')) {
            $rule = explode('|', $post->get('api.location'));
            
            if (count($rule) === 1) { // TODO: legacy. Remove in Jul 2020
                $redirect = $rule[0];
            } elseif ($rule[0] === 'page') {
                $redirect = get_page_link($rule[1]);
            } elseif ($rule[0] === 'url') {
                $redirect = $rule[1];
            } elseif (($rule[0] === 'callback') && is_callable($rule[1])) {
                $redirect = call_user_func($rule[1], $post);
            } else {
                $redirect = null;
            }
            
            $result = new WP_Error(
                'rest_post_cannot_read', 
                "Direct access is not allowed. Follow the redirect link.", 
                array(
                    'action'   => 'api.redirect',
                    'redirect' => $redirect,
                    'status'   => 307
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Check PASSWORD PROTECTED option
     * 
     * @param AAM_Core_Object_Post $post
     * @param WP_REST_Request      $request
     * 
     * @return null|WP_Error
     * 
     * @access public
     */
    public function checkPassword(AAM_Core_Object_Post $post, $request) {
        $result = null;
        
        if ($post->has('api.protected')) {
            $pass = $post->get('api.password');
            
            // initialize hasher
            require_once( ABSPATH . 'wp-includes/class-phpass.php' );
            $hasher = new PasswordHash(8, true);

            if ($pass !== $request['password'] 
                    && !$hasher->CheckPassword($pass, $request['password'])) {
                $result = new WP_Error(
                    'rest_post_cannot_read', 
                    "The content is password protected. Provide valid password to read.", 
                    array(
                        'action'   => 'api.protected',
                        'status'   => 401
                    )
                );
            }
            
            // Very important! Unset password. Otherwise it will fall back to the
            // default password verification and this will cause invalid password
            // response
            $request['password'] = null;
        }
        
        return $result;
    }
    
    /**
     * Check PUBLISH & PUBLISH_BY_OTHERS options
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkPublish(AAM_Core_Object_Post $post) {
        $result = null;
        
        // Keep this compatible with older version of Publish (without Gutenberg)
        if (!$post->allowed('api.publish') || !$post->allowed('backend.publish')) {
            $result = new WP_Error(
                'rest_post_cannot_publish', 
                "User is unauthorized to publish the post. Access denied.", 
                array(
                    'action' => 'api.publish',
                    'status' => 401
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Check EDIT & EDIT_BY_OTHERS options
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkUpdate(AAM_Core_Object_Post $post) {
        $result = null;
        
        if (!$post->allowed('api.edit')) {
            $result = new WP_Error(
                'rest_post_cannot_update', 
                "User is unauthorized to update the post. Access denied.", 
                array(
                    'action' => 'api.edit',
                    'status' => 401
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Check DELETE & DELETE_BY_OTHERS options
     * 
     * @param AAM_Core_Object_Post $post
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkDelete(AAM_Core_Object_Post $post) {
        $result = null;
        
        if (!$post->allowed('api.delete')) {
            $result = new WP_Error(
                'rest_post_cannot_delete', 
                "User is unauthorized to delete the post. Access denied.", 
                array(
                    'action' => 'api.delete',
                    'status' => 401
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Alias for the bootstrap
     * 
     * @return AAM_Api_Rest_Resource_Post
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
     * @return AAM_Api_Rest_Resource_Post
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