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
        $response = array(
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'draw'            => AAM_Core_Request::request('draw'),
            'data'            => array(),
        );
        
        if (current_user_can('list_users')) { 
            //get total number of users
            $total  = count_users();
            $result = $this->query();
            
            $response['recordsTotal']    = $total['total_users'];
            $response['recordsFiltered'] = $result->get_total();

            foreach ($result->get_results() as $row) {
                $response['data'][] = $this->prepareRow(
                        new AAM_Core_Subject_User($row->ID)
                );
            }
        }

        return json_encode($response);
    }
    
    /**
     * Save user expiration
     * 
     * @return string
     * 
     * @access public
     */
    public function saveExpiration() {
        $response = array(
            'status' => 'failure',
            'reason' => __('Operation is not permitted', AAM_KEY)
        );
        
        $userId  = filter_input(INPUT_POST, 'user');
        $expires = filter_input(INPUT_POST, 'expires');
        $action  = filter_input(INPUT_POST, 'after');
        $role    = filter_input(INPUT_POST, 'role');
        
        if (current_user_can('edit_users')) {
            if ($userId != get_current_user_id()) {
                if ($this->isAllowed(new AAM_Core_Subject_User($userId))) {
                    $this->updateUserExpiration($userId, $expires, $action, $role);
                    $response['status'] = 'success';
                }
            } else {
                $response['reason'] = __('You cannot set expiration to yourself', AAM_KEY);
            }
        }
        
        return json_encode($response);
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
        $role   = trim(AAM_Core_Request::request('role'));
        
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
        
        if (!empty($role)) {
            $args['role__in'] = $role;
        }

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
        $result = false;
        
        if (current_user_can('aam_toggle_users') && current_user_can('edit_users')) {
            $subject = AAM_Backend_Subject::getInstance();

            if ($this->isAllowed($subject->get())) {
                //user is not allowed to lock himself
                if ($subject->getId() != get_current_user_id()) {
                    $result = $subject->block();
                }
            }
        }

        return json_encode(array('status' => ($result ? 'success' : 'failure')));
    }
    
    /**
     * Prepare row
     * 
     * @param AAM_Core_Subject_User $user
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareRow(AAM_Core_Subject_User $user) {
        return array(
            $user->ID,
            implode(', ', $this->getUserRoles($user->roles)),
            ($user->display_name ? $user->display_name : $user->user_nicename),
            implode(',', $this->prepareRowActions($user)),
            AAM_Core_API::maxLevel($user->allcaps),
            $this->getUserExpiration($user)
        );
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
    protected function prepareRowActions(AAM_Core_Subject_User $user) {
        if ($this->isAllowed($user) || ($user->ID == get_current_user_id())) {
            $actions = array('manage');
            
            if (AAM_Core_Config::get('core.settings.secureLogin', true) 
                    && current_user_can('aam_toggle_users')) {
                $actions[] = ($user->user_status ? 'unlock' : 'lock');
            }
            
            if (current_user_can('edit_users')) {
                $actions[] = 'edit';
                $actions[] = 'ttl';
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
     * Update user expiration
     * 
     * @param int    $user
     * @param string $expires
     * @param string $action
     * @param string $role
     * 
     * @return bool
     * 
     * @access protected
     */
    protected function updateUserExpiration($user, $expires, $action, $role = '') {
        if (trim($expires)) {
            update_user_meta(
                $user, 
                'aam_user_expiration',
                $expires . "|" . ($action ? $action : 'delete') . '|' . $role
            );
        } else {
            delete_user_meta($user, 'aam_user_expiration');
        }
    }
    
    /**
     * Get user expiration
     * 
     * @param WP_User $user
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getUserExpiration(AAM_Core_Subject_User $user) {
        return get_user_meta($user->ID, 'aam_user_expiration', true);
    }
    
    /**
     * Check max user allowance
     * 
     * @param AAM_Core_Subject_User $user
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isAllowed(AAM_Core_Subject_User $user) {
        $max = AAM_Core_API::maxLevel(AAM::getUser()->allcaps);
        
        return $max >= AAM_Core_API::maxLevel($user->allcaps);
    }

}