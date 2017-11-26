<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role view manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Subject_Role {
    
    /**
     * Get role list
     * 
     * Prepare and return the list of roles for the table view
     * 
     * @return string JSON Encoded role list
     * 
     * @access public
     */
    public function getTable() {
        if (current_user_can('aam_list_roles')) {
            //retrieve list of users
            $count = count_users();
            $stats = $count['avail_roles'];

            $filtered = $this->fetchRoleList();

            $response = array(
                'recordsTotal'    => count(get_editable_roles()),
                'recordsFiltered' => count($filtered),
                'draw'            => AAM_Core_Request::request('draw'),
                'data'            => array(),
            );

            foreach ($filtered as $id => $data) {
                $uc = (isset($stats[$id]) ? $stats[$id] : 0);

                $response['data'][] = array(
                    $id,
                    $uc,
                    translate_user_role($data['name']),
                    apply_filters(
                            'aam-role-row-actions-filter', 
                            implode(',', $this->prepareRowActions($uc)),
                            $data
                    ),
                    AAM_Core_API::maxLevel($data['capabilities']),
                    AAM_Core_API::getOption("aam-role-{$id}-expiration", '')
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
        
        return json_encode(apply_filters('aam-get-role-list-filter', $response));
    }
    
    /**
     * 
     * @param type $count
     * @return string
     */
    protected function prepareRowActions($count) {
        $actions = array('manage');
        
        if (current_user_can('aam_edit_roles')) {
            $actions[] = 'edit';
        }
        if (current_user_can('aam_create_roles')) {
            $actions[] = 'clone';
        }
        if (current_user_can('aam_delete_roles') && !$count) {
            $actions[] = 'delete';
        }
        
        return $actions;
    }
    
    /**
     * Retrieve Pure Role List
     * 
     * @return string
     */
    public function getList(){
        return json_encode(
                apply_filters('aam-get-role-list-filter', $this->fetchRoleList())
        );
    }
    
    /**
     * Fetch role list
     * 
     * @return array
     * 
     * @access protected
     */
    protected function fetchRoleList() {
        $response = array();
         
        //filter by name
        $search  = trim(AAM_Core_Request::request('search.value'));
        $exclude = trim(AAM_Core_Request::request('exclude'));
        $roles   = get_editable_roles();
        
        foreach ($roles as $id => $role) {
            $match = preg_match('/^' . $search . '/i', $role['name']);
            if (($exclude != $id) && (!$search || $match)) {
                $response[$id] = $role;
            }
        }
        
        return $response;
    }

    /**
     * Add New Role
     * 
     * @return string
     * 
     * @access public
     */
    public function add() {
        $response = array('status' => 'failure');
        
        if (current_user_can('aam_create_roles')) {
            $name    = sanitize_text_field(filter_input(INPUT_POST, 'name'));
            $expire  = filter_input(INPUT_POST, 'expire');
            $roles   = AAM_Core_API::getRoles();
            $role_id = strtolower($name);

            //if inherited role is set get capabilities from it
            $parent = $roles->get_role(trim(filter_input(INPUT_POST, 'inherit')));
            $caps   = ($parent ? $parent->capabilities : array());

            if ($role = $roles->add_role($role_id, $name, $caps)) {
                $response = array(
                    'status' => 'success',
                    'role'   => array(
                        'id'    => $role_id,
                        'name'  => $name,
                        'level' => AAM_Core_API::maxLevel($caps)
                    )
                );
                //clone settings if needed
                if (AAM_Core_Request::post('clone')) {
                    $this->cloneSettings($role, $parent);
                }
                
                //save expiration rule if set
                if ($expire) {
                    AAM_Core_API::updateOption("aam-role-{$role_id}-expiration", $expire);
                } else {
                    AAM_Core_API::deleteOption("aam-role-{$role_id}-expiration");
                }
                
                do_action('aam-post-add-role-action', $role, $parent);
            }
        }

        return json_encode($response);
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $role
     * @param type $parent
     */
    protected function cloneSettings($role, $parent) {
        global $wpdb;
        
        //clone _options settings
        $oquery = "SELECT * FROM {$wpdb->options} WHERE `option_name` LIKE %s";
        if ($wpdb->query($wpdb->prepare($oquery, 'aam_%_role_' . $parent->name))) {
            foreach($wpdb->last_result as $setting) {
                AAM_Core_API::updateOption(
                    str_replace($parent->name, $role->name, $setting->option_name), 
                    maybe_unserialize($setting->option_value)
                );
            }
        }
        
        //clone _postmeta settings
        $pquery = "SELECT * FROM {$wpdb->postmeta} WHERE `meta_key` LIKE %s";
        if ($wpdb->query($wpdb->prepare($pquery, 'aam-%-role' . $parent->name))) {
            foreach($wpdb->last_result as $setting) {
                add_post_meta(
                    $setting->post_id, 
                    str_replace($parent->name, $role->name, $setting->meta_key), 
                    maybe_unserialize($setting->meta_value)
                );
            }
        }
    }
    
    /**
     * Edit role name
     * 
     * @return string
     * 
     * @access public
     */
    public function edit() {
        if (current_user_can('aam_edit_roles')) {
            $role    = AAM_Backend_Subject::getInstance();
            $role->update(trim(filter_input(INPUT_POST, 'name')));
            
            $expire  = filter_input(INPUT_POST, 'expire');
            //save expiration rule if set
            if ($expire) {
                AAM_Core_API::updateOption(
                        'aam-role-' . $role->getId() .'-expiration', $expire
                );
            } else { 
                AAM_Core_API::deleteOption('aam-role-' . $role->getId() .'-expiration');
            }

            do_action('aam-post-update-role-action', $role->get());
            
            $response = array('status' => 'success');
        } else {
            $response = array('status' => 'failure');
        }
        
        return json_encode($response);
    }

    /**
     * Delete role
     * 
     * @return string
     * 
     * @access public
     */
    public function delete() {
        $status = 'failure';
        
        if (current_user_can('aam_delete_roles')) {
            if (AAM_Backend_Subject::getInstance()->delete()) {
                $status = 'success';
            }
        }

        return json_encode(array('status' => $status));
    }

}