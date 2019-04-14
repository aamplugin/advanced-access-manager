<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post visibility object
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Object_Visibility extends AAM_Core_Object {

    /**
     * Constructor
     *
     * @param AAM_Core_Subject $subject
     *
     * @return void
     *
     * @access public
     */
    public function __construct(AAM_Core_Subject $subject) {
        parent::__construct($subject);

        $this->initialize();
    }
    
    /**
     * 
     * @global type $wpdb
     */
    public function initialize() {
        global $wpdb;
        
        $subject = $this->getSubject();
        
        // Read cache first
        $option = $subject->getObject('cache')->get('visibility', 0);
        
        if ($option === false) { //if false, then the cache is empty but exists
            $option = array();
        } elseif (empty($option)) {
            $query  = "SELECT pm.`post_id`, pm.`meta_value`, p.`post_type` ";
            $query .= "FROM {$wpdb->postmeta} AS pm ";
            $query .= "LEFT JOIN {$wpdb->posts} AS p ON (pm.`post_id` = p.ID) ";
            $query .= "WHERE pm.`meta_key` = %s";
            
            if ($wpdb->query($wpdb->prepare($query, $this->getOptionName('post')))) {
                foreach($wpdb->last_result as $row) {
                    $settings = maybe_unserialize($row->meta_value);
                    $this->pushOptions('post', $row->post_id . '|' . $row->post_type, $settings);
                }
            }

            // Read all the settings from the Access & Security Policies
            $area = AAM_Core_Api_Area::get();
            $stms = AAM_Core_Policy_Factory::get($subject)->find("/^post:(.*):list$/");

            foreach($stms as $key => $stm) {
                $chunks = explode(':', $key);

                if (is_numeric($chunks[2])) {
                    $postId = $chunks[2];
                } else {
                    $post = get_page_by_path(
                        $chunks[2], OBJECT, $chunks[1]
                    );
                    $postId = (is_a($post, 'WP_Post') ? $post->ID : 0);
                }

                // Cover the case when unknown slug is used
                if (!empty($postId)) { 
                    $this->pushOptions(
                        'post', 
                        "{$postId}|{$chunks[1]}", 
                        array(
                            "{$area}.list" => ($stm['Effect'] === 'deny' ? 1 : 0)
                        )
                    );
                }
            }

            do_action('aam-visibility-initialize-action', $this);
            
            // inherit settings from parent
            $option = $subject->inheritFromParent('visibility', 0);
            if (!empty($option)) {
                $option = array_replace_recursive($option, $this->getOption());
            } else {
                $option = $this->getOption();
            }
            
            if (in_array($subject::UID, array('user', 'visitor'), true)) {
                $subject->getObject('cache')->add(
                    'visibility', 0, empty($option) ? false : $option
                );
            }
        }
        
        $this->setOption($option);
    }
    
    /**
     * 
     * @param type $object
     * @param type $id
     * @param type $options
     * @return type
     */
    public function pushOptions($object, $id, $options) {
        $filtered    = array();
        $listOptions = apply_filters(
            'aam-post-list-options-filter', 
            array('frontend.list', 'backend.list', 'api.list')
        );
        
        foreach($options as $key => $value) {
            if (in_array($key, $listOptions, true)) {
                $filtered[$key] = $value;
            }
        }
        
        if (empty($filtered)) {
            $filtered = array_combine(
                $listOptions, 
                array_fill(0, count($listOptions), 0)
            );
        }
        
        $option = $this->getOption();
        if (!isset($option[$object][$id])) {
            $option[$object][$id] = $filtered;
        }
        $this->setOption($option);
        
        return $filtered;
    }
    
    /**
     * 
     * @param type $object
     * @param type $id
     * @return type
     */
    public function has($object, $id = null) {
        $option = $this->getOption();
        
        return (is_null($id) ? isset($option[$object]) : isset($option[$object][$id]));
    }
    
    /**
     * Generate option name
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getOptionName($object) {
        $subject = $this->getSubject();
        
        //prepare option name
        $meta_key = 'aam-' . $object . '-access-' . $subject->getUID();
        $meta_key .= ($subject->getId() ? $subject->getId() : '');

        return $meta_key;
    }

    /**
     * 
     * @param type $external
     * @return type
     */
    public function mergeOption($external) {
        return AAM::api()->mergeSettings($external, $this->getOption(), 'post');
    }

}