<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM frontend RESTful authorization
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @todo Rethink about DRY approach to the post access control
 */
class AAM_Frontend_Rest {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Frontend_Rest
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     *
     * @var type 
     */
    protected $defaultRoutes = array(
        'posts' => array(
            '/wp/v2/posts/(?P<id>[\d]+)', 
            '/wp/v2/pages/(?P<id>[\d]+)', 
            '/wp/v2/media/(?P<id>[\d]+)'
        )
    );
    
    /**
     * 
     */
    protected function __construct() {
        add_filter('aam-rest-auth-request-filter', array($this, 'authorize'), 10, 4);
        
        add_filter('rest_user_query', array($this, 'userQuery'), 10, 2);
    }
    
    /**
     * 
     * @param type $response
     * @param type $group
     * @param type $request
     * @return type
     */
    public function authorize($response, $group, $request) {
       if ($group == 'posts') {
            $response = $this->authorizePostAction(
                $request['id'], $request->get_method()
            );
        }
        
        return $response;
    }
    
    /**
     * 
     * @param array $args
     * @param type $request
     * @return type
     */
    public function userQuery($args, $request) {
        //current user max level
        $max     = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        $exclude = isset($args['role__not_in']) ? $args['role__not_in'] : array();
        $roles   = AAM_Core_API::getRoles();
        
        foreach($roles->role_objects as $id => $role) {
            if (AAM_Core_API::maxLevel($role->capabilities) > $max) {
                $exclude[] = $id;
            }
        }
        
        $args['role__not_in'] = $exclude;
        
        return $args;
    }
    
    /**
     * 
     * @return type
     */
    public function getRoutes() {
        $posts = AAM_Core_Config::get('restful.routes.posts', array());
        
        $routes = array(
            'posts' => array_merge(
                (is_array($posts) ? $posts : array()), $this->defaultRoutes['posts']
            )
        );
        
        return apply_filters('aam-rest-auth-routes-filter', $routes);
    }
    
    /**
     * 
     * @param type $id
     * @param type $method
     * @return type
     */
    protected function authorizePostAction($id, $method) {
        $post = AAM::getUser()->getObject('post', $id);
        
        switch($method) {
            case 'GET':
                $result = $this->chechReadAuth($post);
                break;

            case 'POST':
            case 'PUT':
            case 'PATCH':
                $result = $this->chechUpdateAuth($post);
                break;

            case 'DELETE':
                $result = $this->chechDeleteAuth($post);
                break;

            default:
                $result = null;
                break;
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
     * 
     * @return void
     * 
     * @access protected
     */
    protected function chechReadAuth(AAM_Core_Object_Post $post) {
        $result = null;
        
        $steps = apply_filters('aam-post-read-auth-pipeline-filter', array(
            // Step #1. Check if access expired to the post
            array($this, 'checkExpiration'),
            // Step #2. Check if user has access to read the post
            array($this, 'checkReadAccess'),
            // Step #3. Check if counter exceeded max allowed views
            array($this, 'checkCounter'),
            // Step #4. Check if redirect is defined for the post
            array($this, 'checkRedirect')
        ));
        
        if (is_array($steps)) {
            foreach($steps as $callback) {
                $result = call_user_func_array($callback, array($post));
                
                if (is_wp_error($result)) {
                    break;
                }
            }
        } else {
            $result = new WP_Error(
                'application_error', 
                "aam-post-read-auth-steps-filter was not utilized properly"
            );
        }
        
        return $result;
    }
    
    /**
     * 
     * @param AAM_Core_Object_Post $post
     * @return type
     */
    protected function chechUpdateAuth(AAM_Core_Object_Post $post) {
        $result = null;
        
        $steps = apply_filters('aam-post-update-auth-steps-filter', array(
            // Step #1. Check if edit action is alloed
            array($this, 'checkUpdate'),
        ));
        
        if (is_array($steps)) {
            foreach($steps as $callback) {
                $result = call_user_func_array($callback, array($post));
                
                if (is_wp_error($result)) {
                    break;
                }
            }
        } else {
            $result = new WP_Error(
                'application_error', 
                "aam-post-update-auth-steps-filter was not utilized properly"
            );
        }
        
        return $result;
    }
    
    /**
     * 
     * @param AAM_Core_Object_Post $post
     * @return type
     */
    protected function chechDeleteAuth(AAM_Core_Object_Post $post) {
        $result = null;
        
        $steps = apply_filters('aam-post-delete-auth-steps-filter', array(
            // Step #1. Check if edit action is alloed
            array($this, 'checkDelete'),
        ));
        
        if (is_array($steps)) {
            foreach($steps as $callback) {
                $result = call_user_func_array($callback, array($post));
                
                if (is_wp_error($result)) {
                    break;
                }
            }
        } else {
            $result = new WP_Error(
                'application_error', 
                "aam-post-delete-auth-steps-filter was not utilized properly"
            );
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
        $expire = $post->has('frontend.expire');

        if ($expire) {
            $date = strtotime($post->get('frontend.expire_datetime'));
            if ($date <= time()) {
                $actions = AAM_Core_Config::get(
                        'post.access.expired', 'frontend.read'
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
        
        $read   = $post->has('frontend.read');
        $others = $post->has('frontend.read_others');
        
        if ($read || ($others && ($post->post_author != get_current_user_id()))) {
            $result = new WP_Error(
                'post_read_access_denied', 
                "User is unauthorized to read the post. Access denied.", 
                array(
                    'action' => 'frontend.read',
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
        if ($user && $post->has('frontend.access_counter')) {
            $option  = 'aam-post-' . $post->ID . '-access-counter';
            $counter = intval(get_user_meta($user, $option, true));
            
            if ($counter >= $post->get('frontend.access_counter_limit')) {
                $result = new WP_Error(
                    'post_read_access_denied', 
                    "User exceeded allowed read number. Access denied.", 
                    array(
                        'action' => 'frontend.access_counter',
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
        
        if ($post->has('frontend.redirect')) {
            $result = new WP_Error(
                'post_read_access_denied', 
                "Direct access is not allowed. Access redirected", 
                array(
                    'action'   => 'frontend.redirect',
                    'redirect' => $post->get('frontend.location'),
                    'status'   => 307
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
        
        $edit   = $post->has('backend.edit');
        $others = $post->has('backend.edit_others');
        
        if ($edit || ($others && ($post->post_author != get_current_user_id()))) {
            $result = new WP_Error(
                'post_update_access_denied', 
                "User is unauthorized to update the post. Access denied.", 
                array(
                    'action' => 'backend.edit',
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
        
        $delete = $post->has('backend.delete');
        $others = $post->has('backend.delete_others');
        
        if ($delete || ($others && ($post->post_author != get_current_user_id()))) {
            $result = new WP_Error(
                'post_delete_access_denied', 
                "User is unauthorized to delete the post. Access denied.", 
                array(
                    'action' => 'backend.delete',
                    'status' => 401
                )
            );
        }
        
        return $result;
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