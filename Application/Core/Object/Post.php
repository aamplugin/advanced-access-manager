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
    public function __construct(AAM_Core_Subject $subject, $post, $param = null) {
        parent::__construct($subject);

        // Make sure that we are dealing with WP_Post object
        // This is done to remove redundant calls to the database on the backend view
        if (is_object($param) && is_a($param, 'WP_Post')) {
            $this->setPost($param);
        } elseif (is_numeric($post)) {
            $this->setPost(get_post($post));
        }

        // Determine if we need to skip inheritance chain from the parent subject
        // This is done to eliminate constrains related to Inherit From Parent Post
        if (is_array($param)) {
            $void = !empty($param['voidInheritance']);
        } else {
            $void = false;
        }
        
        $this->initialize($void);
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
     * 
     */
    public function initialize($voidInheritance = false) {
        if ($this->getPost()) {
            $this->read($voidInheritance);
        }
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
    public function read($voidInheritance = false) {
        $subject = $this->getSubject();
        $post    = $this->getPost();
        
        $option = get_post_meta($post->ID, $this->getOptionName(), true);
        $this->setOverwritten(!empty($option));
        
        // Read settings from access policy
        if (empty($option)) {
            $stms = AAM_Core_Policy_Factory::get($subject)->find(
                "/^post:{$post->post_type}:({$post->post_name}|{$post->ID}):/",
                array('post' => $post)
            );

            $option = array();

            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);
                $action = (isset($chunks[3]) ? $chunks[3] : 'read');
                $meta   = (isset($stm['Metadata']) ? $stm['Metadata'] : array());

                $option = array_merge(
                    $option,
                    AAM_Core_Compatibility::convertPolicyAction(
                        $action,
                        $stm['Effect'] === 'deny',
                        '',
                        ($action === 'read' ? $meta : array()),
                        array($post)
                    )
                );
            }
        }

        // Inherit from terms or default settings - AAM Plus Package
        if (empty($option)) {
            $option = apply_filters('aam-post-access-filter', $option, $this);
        }
        
        // No settings for a post. Try to inherit from the parent
        if (empty($option) && ($voidInheritance === false)) { 
            $option = $subject->inheritFromParent('post', $post->ID, $post);
        }

        $this->setOption($option);
    }
    
    /**
     * Save options
     * 
     * @param string $property
     * @param mixed  $value
     * 
     * @return boolean
     * 
     * @access public
     */
    public function save($property, $value) {
        $option = $this->getOption();
        
        $option[$property] = $value;
        
        // Very specific WP case. According to the WP core, you are not allowed to
        // set meta for revision, so let's bypass this constrain.
        if ($this->getPost()->post_type === 'revision') {
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
        // Very specific WP case. According to the WP core, you are not allowed to
        // set meta for revision, so let's bypass this constrain.
        if ($this->getPost()->post_type === 'revision') {
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
     * @param string $property
     * 
     * @return boolean
     * 
     * @access public
     */
    public function has($property) {
        $option = $this->getOption();

        return (array_key_exists($property, $option) && !empty($option[$property]));
    }
    
    /**
     * Check if subject can do certain action
     * 
     * The difference between `can` and `allowed` is that can is more in-depth way 
     * to take in consideration relationships between properties.
     *  
     * @return boolean
     * 
     * @access public
     */
    public function allowed() {
        return apply_filters(
            'aam-post-action-allowed-filter', 
            !call_user_func_array(array($this, 'has'), func_get_args()), 
            func_get_arg(0), 
            $this
        );
    }
    
    /**
     * Update property
     * 
     * @param string $property
     * @param mixed  $value
     * 
     * @return boolean
     * 
     * @access public
     */
    public function update($property, $value) {
        return $this->save($property, $value);
    }
    
    /**
     * Remove property
     * 
     * @param string $property
     * 
     * @return boolean
     * 
     * @access public
     */
    public function remove($property) {
        $option = $this->getOption();
        
        if (array_key_exists($property, $option)) {
            unset($option[$property]);
        }
        
        // Very specific WP case. According to the WP core, you are not allowed to
        // set meta for revision, so let's bypass this constrain.
        if ($this->getPost()->post_type === 'revision') {
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
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return AAM::api()->mergeSettings($external, $this->getOption(), 'post');
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