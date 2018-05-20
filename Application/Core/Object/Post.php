<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Post extends AAM_Core_Object {

    /**
     * Post object
     * 
     * @var WP_Post
     * 
     * @access private
     */
    private $_post;
    
    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     * @param WP_Post|Int      $post
     *
     * @return void
     *
     * @access public
     */
    public function __construct(AAM_Core_Subject $subject, $post) {
        parent::__construct($subject);

        //make sure that we are dealing with WP_Post object
        if (is_object($post)) {
            $this->setPost($post);
        } elseif (intval($post)) {
            $this->setPost(get_post($post));
        }
        
        if ($this->getPost()) {
            $this->read();
        }
    }
    
    /**
     * Get WP post property
     * 
     * @param string $name
     * 
     * @return mixed
     * 
     * @access public
     */
    public function __get($name) {
        $post = $this->getPost();
        
        return (is_object($post) && property_exists($post, $name) ? $post->$name : null);
    }

    /**
     * Read the Post AAM Metadata
     *
     * Get all settings related to specified post.
     *
     * @return void
     *
     * @access public
     */
    public function read() {
        $subject = $this->getSubject();
        $post    = $this->getPost();
        
        // Read cache first
        $option = $subject->getObject('cache')->get('post', $post->ID);
        
        if ($option === false) { //if false, then the cache is empty but exist
            $option = array();
        } elseif (empty($option)) {
            $option = get_post_meta($post->ID, $this->getOptionName(), true);
            $this->setOverwritten(!empty($option));
            
            // Inherit from terms or default settings - AAM Plus Package
            if (empty($option)) {
                $option = apply_filters('aam-post-access-filter', $option, $this);
            }
            
            // Cache result but only if it is not empty
            if (!empty($option)) {
                $subject->getObject('cache')->add('post', $post->ID, $option);
            } else { // No settings for a post. Try to inherit from the parent
                $option = $subject->inheritFromParent('post', $post->ID, $post);
            }
            
            // Do not perform finalization if this is user level subject unless it
            // is overriten. This is critical to avoid overloading database with too 
            // much cache
            if ($this->allowCache($subject) || $this->isOverwritten()) {
                $this->finalizeOption($post, $subject, $option);
            }
        }
        
        $this->setOption($option);
    }
    
    /**
     * 
     * @param type $subject
     * @return type
     * @todo This does not belong here
     */
    protected function allowCache($subject) {
        $config = AAM_Core_Config::get(
                'core.cache.post.levels', array('role', 'visitor', 'user')
        );
        
        return is_array($config) && in_array($subject::UID, $config);
    }
    
    /**
     * Finalize post options
     * 
     * @param WP_Post          $post
     * @param AAM_Core_Subject $subject
     * @param array            &$option
     * 
     * @return void
     * 
     * @access protected
     */
    protected function finalizeOption($post, $subject, &$option) {
        // If result is empty, simply cache the false to speed-up but do not
        // do it on the use level to avoid overloading database with too much cache
        if (empty($option)) {
            $subject->getObject('cache')->add('post', $post->ID, false);
        } else {
            $subject->getObject('cache')->add('post', $post->ID, $option);
            
            // Determine if post is hidden or not. This is more complex calculation
            // as it is based on the combination of several options
            // TODO: this check does not belong here
            if (in_array($subject::UID, array('user'))) {
                $this->determineVisibility($post, 'frontend', $option);
                $this->determineVisibility($post, 'backend', $option);
                $this->determineVisibility($post, 'api', $option);
            }
        }
    }
    
    /**
     * Determine if post is visible for current subject
     * 
     * @param WP_Post $post
     * @param string  $area
     * 
     * @param boolean $option
     * 
     * @access protected
     */
    protected function determineVisibility($post, $area, &$option) {
        $list   = !empty($option["{$area}.list"]);
        $others = !empty($option["{$area}.list_others"]);
        
        if ($list || ($others && ($post->post_author != $this->getSubject()->ID))) {
            $option["{$area}.hidden"] = true;
            
            // Cache result but only if visibility is true!
            $this->getSubject()->getObject('cache')->add('post', $post->ID, $option);
        }
    }
    
    /**
     * Save options
     * 
     * @return boolean
     * 
     * @access public
     */
    public function save($property, $checked) {
        $option = $this->getOption();
        
        $option[$property] = $checked;
        
        // Very specific WP case. According to the WP core, you are not allowed to
        // set meta for revision, so let's bypass this constrain.
        if ($this->getPost()->post_type == 'revision') {
            $result =  update_metadata(
                'post', $this->getPost()->ID, $this->getOptionName(), $option
            );
        } else {
            $result = update_post_meta(
                    $this->getPost()->ID, $this->getOptionName(), $option
            );
        }
        
        if ($result) {
            $this->setOption($option);
        }
        
        return $result;
    }
    
    /**
     * Reset post settings
     * 
     * @return boolean
     * 
     * @access public
     */
    public function reset() {
        AAM_Core_API::clearCache();
        
        // Very specific WP case. According to the WP core, you are not allowed to
        // set meta for revision, so let's bypass this constrain.
        if ($this->getPost()->post_type == 'revision') {
            $result = delete_metadata(
                    'post', $this->getPost()->ID, $this->getOptionName()
            );
        } else {
            $result = delete_post_meta($this->getPost()->ID, $this->getOptionName());
        }
        
        return $result;
    }

    /**
     * Set Post
     *
     * @param WP_Post|stdClass $post
     *
     * @return void
     *
     * @access public
     */
    public function setPost($post) {
        $this->_post = $post;
    }

    /**
     * Generate option name
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getOptionName() {
        $subject = $this->getSubject();
        
        //prepare option name
        $meta_key = 'aam-post-access-' . $subject->getUID();
        $meta_key .= ($subject->getId() ? $subject->getId() : '');

        return $meta_key;
    }

    /**
     * Check if option is set
     * 
     * @param string $area
     * @param string $action
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($action) {
        $option = $this->getOption();

        return (isset($option[$action]) && $option[$action]);
    }

    /**
     * Get option
     * 
     * @param string $area
     * @param string $action
     * 
     * @return boolean
     * 
     * @access public
     */
    public function get($action) {
        $option = $this->getOption();

        return (isset($option[$action]) ? $option[$action] : null);
    }
    
    /**
     * Set option
     * 
     * Set property without storing to the database for cased like "expire".
     * 
     * @param string $property
     * @param mixed $value
     * 
     * @return boolean
     * 
     * @access public
     */
    public function set($property, $value) {
        $option = $this->getOption();
        
        $option[$property] = $value;
        
        $this->setOption($option);
        
        return true;
    }
    
    /**
     * Get Post
     *
     * @return WP_Post|stdClass
     *
     * @access public
     */
    public function getPost() {
        return $this->_post;
    }
    
}