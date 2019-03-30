<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend capability manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_Capability extends AAM_Backend_Feature_Abstract {
    
    /**
     * Capability groups
     * 
     * @var array
     * 
     * @access private
     */
    public static $groups = array(
        'system' => array(
            'level_0', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5',
            'level_6', 'level_7', 'level_8', 'level_9', 'level_10'
        ),
        'post' => array(
            'delete_others_pages', 'delete_others_posts', 'edit_others_pages',
            'delete_posts', 'delete_private_pages', 'delete_private_posts',
            'delete_published_pages', 'delete_published_posts', 'delete_pages',
            'edit_others_posts', 'edit_pages', 'edit_private_posts',
            'edit_private_pages', 'edit_posts', 'edit_published_pages',
            'edit_published_posts', 'publish_pages', 'publish_posts', 'read',
            'read_private_pages', 'read_private_posts', 'edit_permalink'
        ),
        'backend' => array(
            'activate_plugins', 'add_users', 'update_plugins',
            'delete_users', 'delete_themes', 'edit_dashboard', 'edit_files',
            'edit_plugins', 'edit_theme_options', 'edit_themes', 'edit_users',
            'export', 'import', 'install_plugins', 'install_themes',
            'manage_options', 'manage_links', 'manage_categories', 'customize',
            'unfiltered_html', 'unfiltered_upload', 'update_themes',
            'update_core', 'upload_files', 'delete_plugins', 'remove_users',
            'switch_themes', 'list_users', 'promote_users', 'create_users', 'delete_site'
        ),
        'aam' => array(
            'aam_manage_admin_menu', 'aam_manage_metaboxes', 'aam_manage_capabilities',
            'aam_manage_posts', 'aam_manage_access_denied_redirect', 'aam_create_roles',
            'aam_manage_login_redirect', 'aam_manage_logout_redirect', 'aam_manager',
            'aam_manage_settings', 'aam_manage_extensions', 'aam_show_notifications', 
            'aam_manage_404_redirect', 'aam_manage_ip_check', 'aam_manage_admin_toolbar',
            'aam_manage_default', 'aam_manage_visitors', 'aam_manage_roles', 'aam_manage_users',
            'aam_edit_roles', 'aam_delete_roles', 'aam_toggle_users', 'aam_switch_users',
            'aam_manage_configpress', 'aam_manage_api_routes', 'aam_manage_uri', 'aam_manage_policy',
            'aam_view_help_btn', 'aam_edit_policy', 'aam_read_policy', 'aam_delete_policy',
            'aam_delete_policies', 'aam_edit_policies', 'aam_edit_others_policies', 'aam_publish_policies',
            'aam_manage_jwt'
        )
    );
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        $allowed = AAM_Backend_Subject::getInstance()->isAllowedToManage();
        if (!$allowed || !current_user_can('aam_manage_capabilities')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_capabilities'));
        }
    }
    
    /**
     * Undocumented function
     *
     * @return void
     */
    public function save() {
       $cap    = AAM_Core_Request::post('capability');
       $status = AAM_Core_Request::post('status');

       $object = AAM_Backend_Subject::getInstance()->getObject('capability');

       $object->save($cap, $status);

       return wp_json_encode(array('status' => 'success'));
    }
    
    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject('capability');
    }

    /**
     *
     * @return type
     */
    public function getTable() {
        $response = array('data' => $this->retrieveAllCaps());

        return wp_json_encode($response);
    }
    
    /**
     * Update capability tag
     * 
     * @return string
     * 
     * @access public
     */
    public function update() {
        $capability = AAM_Core_Request::post('capability');
        $updated    = AAM_Core_Request::post('updated');
        $roles      = AAM_Core_API::getRoles();
        
        if ($this->isAllowedToEdit($capability) === false) {
            $response = array(
                'status'  => 'failure', 
                'message' => __('Permission denied to update this capability', AAM_KEY)
            );
        } elseif (AAM_Core_API::capabilityExists($updated) === false) {
            foreach($roles->role_objects as $role) {
                //check if capability is present for current role! Note, we
                //can not use the native WP_Role::has_cap function because it will
                //return false if capability exists but not checked
                if (is_array($role->capabilities) 
                        && array_key_exists($capability, $role->capabilities)) {
                    $role->add_cap($updated, $role->capabilities[$capability]);
                    $role->remove_cap($capability);
                }
            }
            $response = array('status' => 'success');
        } else {
            $response = array(
                'status'  => 'failure', 
                'message' => __('Capability already exists', AAM_KEY)
            );
        }
        
        return wp_json_encode($response);
    }
    
    /**
     * Delete capability
     * 
     * This function delete capability in all roles.
     * 
     * @return string
     * 
     * @access public
     */
    public function delete() {
        $capability = AAM_Core_Request::post('capability');
        $roles      = AAM_Core_API::getRoles();
        
        if ($this->isAllowedToDelete($capability) === false) {
            $response = array(
                'status'  => 'failure', 
                'message' => __('Permission denied to delete this capability', AAM_KEY)
            );
        } else {
            foreach($roles->role_objects as $role) {
                $role->remove_cap($capability);
            }
            $response = array('status' => 'success');
        }
        
        return wp_json_encode($response);
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/capability.phtml';
    }
    
    /**
     * 
     * @param type $cap
     * @return type
     */
    protected function prepareActionList($cap) {
        $subject = AAM_Backend_Subject::getInstance();
        $actions = array();
        
        $toggle = ($subject->hasCapability($cap) ? 'checked' : 'unchecked');
        
        if (AAM::api()->getPolicyManager()->isAllowed("Capability:{$cap}:AAM:toggle") === false) {
            $toggle = 'no-' . $toggle;
        }
        
        $actions[] = $toggle;
        
        //allow to delete or update capability only for roles!
        $edit   = 'edit';
        $delete = 'delete';

        if ($this->isAllowedToEdit($cap) === false) {
            $edit = 'no-' . $edit;
        }

        if ($this->isAllowedToDelete($cap) === false) {
            $delete = 'no-' . $delete;
        }

        $actions[] = $edit;
        $actions[] = $delete;
        
        return implode(',', $actions);
    }
    
    /**
     * 
     * @param type $subject
     * @param type $cap
     * @return boolean
     */
    protected function isAllowedToEdit($cap) {
        $allowed = false;
        
        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        if (AAM::api()->getPolicyManager()->isAllowed("Capability:{$cap}:AAM:update") === false) {
            $allowed = false;
        }
        
        return $allowed;
    }
    
    /**
     * 
     * @param type $subject
     * @param type $cap
     * @return boolean
     */
    protected function isAllowedToDelete($cap) {
        $allowed = false;
        
        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        if (AAM::api()->getPolicyManager()->isAllowed("Capability:{$cap}:AAM:delete") === false) {
            $allowed = false;
        }
        
        return $allowed;
    }

    /**
     * 
     * @return type
     */
    protected function retrieveAllCaps() {
        $response = array();
        $manager  = AAM::api()->getPolicyManager();
        
        foreach (array_keys(AAM_Core_API::getAllCapabilities()) as $cap) {
            if ($manager->isAllowed("Capability:{$cap}:AAM:list") !== false) {
                $response[] = array(
                    $cap,
                    $this->getGroup($cap),
                    $cap,
                    $this->prepareActionList($cap)
                );
            }
        }
        
        return $response;
    }

    /**
     * Get capability group list
     * 
     * @return array
     * 
     * @access public
     */
    public function getGroupList() {
        return apply_filters('aam-capability-groups-filter', array(
            __('System', AAM_KEY),
            __('Posts & Pages', AAM_KEY),
            __('Backend', AAM_KEY),
            __('AAM Interface', AAM_KEY),
            __('Miscellaneous', AAM_KEY)
        ));
    }

    /**
     * Add new capability
     * 
     * @return string
     * 
     * @access public
     */
    public function add() {
        $capability = sanitize_text_field(AAM_Core_Request::post('capability'));

        if ($capability) {
            //add the capability to administrator's role as default behavior
            AAM_Core_API::getRoles()->add_cap('administrator', $capability);
            AAM_Backend_Subject::getInstance()->addCapability($capability);
            $response = array('status' => 'success');
        } else {
            $response = array('status' => 'failure');
        }

        return wp_json_encode($response);
    }

    /**
     * Get capability group name
     * 
     * @param string $capability
     * 
     * @return string
     * 
     * @access protected
     */
    protected function getGroup($capability) {
        if (in_array($capability, self::$groups['system'], true)) {
            $response = __('System', AAM_KEY);
        } elseif (in_array($capability, self::$groups['post'], true)) {
            $response = __('Posts & Pages', AAM_KEY);
        } elseif (in_array($capability, self::$groups['backend'], true)) {
            $response = __('Backend', AAM_KEY);
        } elseif (in_array($capability, self::$groups['aam'], true)) {
            $response = __('AAM Interface', AAM_KEY);
        } else {
            $response = __('Miscellaneous', AAM_KEY);
        }

        return apply_filters(
                'aam-capability-group-filter', $response, $capability
        );
    }
    
    /**
     * Check overwritten status
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isOverwritten() {
        $object = AAM_Backend_Subject::getInstance()->getObject('capability');
        
        return $object->isOverwritten();
    }
    
    /**
     * Register capability feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'capability',
            'position'   => 15,
            'title'      => __('Capabilities', AAM_KEY),
            'capability' => 'aam_manage_capabilities',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID
            ),
            'view'       => __CLASS__
        ));
    }

}