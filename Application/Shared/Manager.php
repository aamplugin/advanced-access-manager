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
        }
        
        return self::$_instance;
    }
    
    /**
     * 
     * @global type $wp_admin_bar
     */
    public function filterToolbar() {
        global $wp_admin_bar;
        
        $toolbar = AAM::api()->getUser()->getObject('toolbar');
        
        //echo '<pre>'; print_r($wp_admin_bar->get_nodes()); die();
        
        foreach($wp_admin_bar->get_nodes() as $id => $node) {
            if ($toolbar->has($id, true)) {
                if (!empty($node->parent)) { // update parent node with # link
                    $parent = $wp_admin_bar->get_node($node->parent);
                    if ($parent && ($parent->href == $node->href)) {
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
     * @global WPDB $wpdb
     */
    public function filterPostQuery($clauses, $wpQuery) {
        if (!$wpQuery->is_singular && $this->isPostFilterEnabled()) {
            $option = AAM::getUser()->getObject('visibility')->getOption();
            
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
        
        if ($postType == 'any') {
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

            if (in_array($chunks[1], $postTypes)) {
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