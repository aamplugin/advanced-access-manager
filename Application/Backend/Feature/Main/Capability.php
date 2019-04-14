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
     * Save capability status
     *
     * @return string
     * 
     * @access public
     */
    public function save() {
       $cap    = AAM_Core_Request::post('capability');
       $status = AAM_Core_Request::post('status');

       $object = AAM_Backend_Subject::getInstance()->getObject('capability');
       $result = $object->save($cap, $status);

        return wp_json_encode(array(
           'status' => ($result ? 'success' : 'failure')
        ));
    }
    
    /**
     * Reset capabilities
     * 
     * @return string
     * 
     * @access public
     */
    public function reset() {
        $result = AAM_Backend_Subject::getInstance()->resetObject('capability');

        return wp_json_encode(array(
            'status' => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Get list of capabilities for table view
     * 
     * @return string
     * 
     * @access public
     */
    public function getTable() {
        $data     = array();
        $subject  = AAM_Backend_Subject::getInstance();
        $manager  = AAM::api()->getPolicyManager();

        // Compile the complete list of capabilities
        $caps = AAM_Core_API::getAllCapabilities();

        // Add also subject specific capabilities
        $caps = array_merge($caps, $subject->getCapabilities());

        foreach (array_keys($caps) as $cap) {
            if ($manager->isAllowed("Capability:{$cap}:AAM:list") !== false) {
                $data[] = array(
                    $cap,
                    $this->getGroup($cap),
                    $cap,
                    $this->prepareActionList($cap)
                );
            }
        }

        return wp_json_encode(array('data' => $data));
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/capability.phtml';
    }
    
    /**
     * Prepare row actions
     * 
     * Based on current user permissions and subject's capability ownership, prepare
     * the correct list of actions
     * 
     * @param string $cap
     * 
     * @return string
     * 
     * @access protected
     */
    protected function prepareActionList($cap) {
        $subject = AAM_Backend_Subject::getInstance();
        $actions = array();
        
        $toggle  = ($subject->hasCapability($cap) ? 'checked' : 'unchecked');
        $manager = AAM::api()->getPolicyManager();

        if ($manager->isAllowed("Capability:{$cap}:AAM:toggle") === false) {
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
     * Check if current user can edit capability
     * 
     * @param string $cap
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isAllowedToEdit($cap) {
        $allowed = false;
        
        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        $manager = AAM::api()->getPolicyManager();
        if ($manager->isAllowed("Capability:{$cap}:AAM:update") === false) {
            $allowed = false;
        }

        // Check if current subject contains the capability and if so, allow to
        // edit it
        $subject = AAM_Backend_Subject::getInstance();
        if ($allowed) {
            $allowed = array_key_exists($cap, $subject->getCapabilities());
        } 
        
        return $allowed;
    }
    
    /**
     * Check if current user can delete capability
     * 
     * @param string $cap
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isAllowedToDelete($cap) {
        $allowed = false;
        
        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        $manager = AAM::api()->getPolicyManager();
        if ($manager->isAllowed("Capability:{$cap}:AAM:delete") === false) {
            $allowed = false;
        }

        // Check if current subject contains the capability and if so, allow to
        // delete it
        $subject = AAM_Backend_Subject::getInstance();
        if ($allowed) {
            $allowed = array_key_exists($cap, $subject->getCapabilities());
        } 
        
        return $allowed;
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
            $result = AAM_Backend_Subject::getInstance()->addCapability($capability);
            
            $response = array('status' => ($result ? 'success' : 'failure'));
        } else {
            $response = array('status' => 'failure');
        }

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
        $subject    = AAM_Backend_Subject::getInstance();
        
        if ($this->isAllowedToEdit($capability) === false) {
            $response = array(
                'status'  => 'failure', 
                'message' => __('Permission denied to update this capability', AAM_KEY)
            );
        } else {
            if ($subject->removeCapability($capability)) {
                $result = $subject->addCapability($updated);
            }

            $response = array('status' => (!empty($result) ? 'success' : 'failure'));
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
        $subject    = AAM_Backend_Subject::getInstance();
        
        if ($this->isAllowedToDelete($capability) === false) {
            $response = array(
                'status'  => 'failure', 
                'message' => __('Permission denied to delete this capability', AAM_KEY)
            );
        } else {
            $result   = $subject->removeCapability($capability);
            $response = array('status' => ($result ? 'success' : 'failure'));
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