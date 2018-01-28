<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User view manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Subject_User {
    
    /**
     * Retrieve list of users
     * 
     * Based on filters, get list of users
     * 
     * @return string JSON encoded list of users
     * 
     * @access public
     */
    public function getTable() {
        if (current_user_can('list_users')) { 
            //get total number of users
            $total  = count_users();
            $result = $this->query();

            $response = array(
                'recordsTotal'    => $total['total_users'],
                'recordsFiltered' => $result->get_total(),
                'draw'            => AAM_Core_Request::request('draw'),
                'data'            => array(),
            );

            foreach ($result->get_results() as $user) {
                $response['data'][] = array(
                    $user->ID,
                    implode(', ', $this->getUserRoles($user->roles)),
                    ($user->display_name ? $user->display_name : $user->user_nicename),
                    implode(',', $this->prepareRowActions($user)),
                    AAM_Core_API::maxLevel($user->allcaps)
                );
            }
        } else {
            $response = array(
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'draw'            => AAM_Core_Request::request('draw'),
                'data'            => array(),
            );
        }

        return json_encode($response);
    }
    
    /**
     * Get list of user roles
     * 
     * @param array $roles
     * 
     * @return array
     * 
     * @access protected
     */
    protected function getUserRoles($roles) {
        $response = array();
        
        $names = AAM_Core_API::getRoles()->get_names();
        
        if (is_array($roles)) {
            foreach($roles as $role) {
                if (array_key_exists($role, $names)) {
                    $response[] = translate_user_role($names[$role]);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Prepare user row actions
     * 
     * @param WP_User $user
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareRowActions(WP_User $user) {
        $max = AAM_Core_API::maxLevel(wp_get_current_user()->allcaps);
        
        $allowed = ($max >= AAM_Core_API::maxLevel($user->allcaps));
        
        if ($allowed || ($user->ID == get_current_user_id())) {
            $actions = array('manage');
            
            if (AAM_Core_Config::get('secure-login', true)) {
                if (current_user_can('aam_toggle_users')) {
                    $actions[] = ($user->user_status ? 'unlock' : 'lock');
                }
            }
            
            if (current_user_can('edit_users')) {
                $actions[] = 'edit';
            }
            
            if (current_user_can('aam_switch_users')) {
                $actions[] = 'switch';
            }
        } else {
            $actions = array();
        }
        
        return $actions;
    }

    /**
     * Query database for list of users
     * 
     * Based on filters and settings get the list of users from database
     * 
     * @return \WP_User_Query
     * 
     * @access public
     */
    public function query() {
        $search = trim(AAM_Core_Request::request('search.value'));
        
        $args = array(
            'blog_id' => get_current_blog_id(),
            'fields'  => 'all',
            'number'  => AAM_Core_Request::request('length'),
            'offset'  => AAM_Core_Request::request('start'),
            'search'  => ($search ? $search . '*' : ''),
            'search_columns' => array(
                'user_login', 'user_email', 'display_name'
            ),
            'orderby' => 'user_nicename',
            'order'   => 'ASC'
        );

        return new WP_User_Query($args);
    }

    /**
     * Block user
     * 
     * @return string
     * 
     * @access public
     */
    public function block() {
        $result  = false;
        
        if (current_user_can('aam_toggle_users')) {
            $subject = AAM_Backend_Subject::getInstance();

            //user is not allowed to lock himself
            if ($subject->getId() != get_current_user_id()) {
                $result = $subject->block();
            }
        }

        return json_encode(
                array('status' => ($result ? 'success' : 'failure'))
        );
    }

}