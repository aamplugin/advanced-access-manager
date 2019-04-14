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
     * Construct
     */
    public function __construct() {
        if (!current_user_can('aam_manage_roles')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_roles'));
        }
    }
    
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
                    implode(',', $this->prepareRowActions($uc, $id)),
                    $data
                ),
                AAM_Core_API::maxLevel($data['capabilities'])
            );
        }
        
        return wp_json_encode(apply_filters('aam-get-role-list-filter', $response));
    }
    
    /**
     * Prepare the list of role actions
     * 
     * @param int    $count  Number of users in role
     * @param string $roleId Role slug
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareRowActions($count, $roleId) {
        $ui = AAM_Core_Request::post('ui', 'main');
        $id = AAM_Core_Request::post('id');
        
        if ($ui === 'principal') {
            $subject = new AAM_Core_Subject_Role($roleId);
            
            $object  = $subject->getObject('policy');
            $action  = ($object->has($id) ? 'detach' : 'attach');
            $manager = AAM_Core_Policy_Factory::get();
            
            // Verify that current user can perform following action
            $prefix = ($manager->canTogglePolicy($id, $action) ? '' : 'no-');
            
            $actions = array($prefix . $action);
        } else {
            $actions = array('manage');

            if (current_user_can('aam_edit_roles')) {
                $actions[] = 'edit';
            } else {
                $actions[] = 'no-edit';
            }
            if (current_user_can('aam_create_roles')) {
                $actions[] = 'clone';
            } else {
                $actions[] = 'no-clone';
            }
            if (current_user_can('aam_delete_roles') && !$count) {
                $actions[] = 'delete';
            } else {
                $actions[] = 'no-delete';
            }
        }
        
        return $actions;
    }
    
    /**
     * Retrieve Pure Role List
     * 
     * @return string
     */
    public function getList(){
        return wp_json_encode(
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
            if (($exclude !== $id) && (!$search || $match)) {
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
            $roles   = AAM_Core_API::getRoles();
            $role_id = sanitize_key(strtolower($name));

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
                
                do_action('aam-post-add-role-action', $role, $parent);
            } else {
                $response['reason'] = __("Role with slug [{$role_id}] already exists", AAM_KEY);
            }
        }

        return wp_json_encode($response);
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
            $role = AAM_Backend_Subject::getInstance();

            $role->update(esc_js(trim(filter_input(INPUT_POST, 'name'))));
            
            do_action('aam-post-update-role-action', $role->get());
            
            $response = array('status' => 'success');
        } else {
            $response = array('status' => 'failure');
        }
        
        return wp_json_encode($response);
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

        return wp_json_encode(array('status' => $status));
    }

}