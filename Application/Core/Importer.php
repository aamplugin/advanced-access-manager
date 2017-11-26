<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Importer
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Importer {
    
    /**
     *
     * @var type 
     */
    protected $input = null;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $blog = null;
    
    /**
     * 
     * @param type $input
     */
    public function __construct($input, $blog = null) {
        $this->input = json_decode($input);
        $this->blog  = (is_null($blog) ? get_current_blog_id() : $blog);
    }
    
    /**
     * 
     * @return type
     */
    public function run() {
        foreach($this->input->dataset as $table => $data) {
            if ($table == '_options') {
                $this->insertOptions($data);
            } elseif ($table == '_postmeta') {
                $this->insertPostmeta($data);
            } elseif ($table == '_usermeta') {
                $this->insertUsermeta($data);
            } else {
                do_action('aam-import', $table, $data);
            }
        }
        
        return 'success';
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertOptions($data) {
        global $wpdb;
        
        foreach($data as $key => $value) {
            AAM_Core_API::updateOption(
                preg_replace('/^_/', $wpdb->get_blog_prefix($this->blog), $key),
                $this->prepareValue($value),
                $this->blog
            );
        }
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertUsermeta($data) {
        global $wpdb;
        
        foreach($data as $id => $set) {
            foreach($set as $key => $value) {
                update_user_meta(
                        $id, 
                        preg_replace('/^_/', $wpdb->get_blog_prefix($this->blog), $key), 
                        $this->prepareValue($value)
                );
            }
        }
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertPostmeta($data) {
        global $wpdb;
         
        foreach($data as $id => $set) {
            foreach($set as $key => $value) {
                update_post_meta(
                        $id, 
                        preg_replace('/^_/', $wpdb->prefix, $key), 
                        $this->prepareValue($value)
                );
            }
        }
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $value
     * @return void
     */
    protected function prepareValue($value) {
        if (is_serialized($value)) {
            $value = unserialize($value);
        }
        
        return $value;
    }
    
}