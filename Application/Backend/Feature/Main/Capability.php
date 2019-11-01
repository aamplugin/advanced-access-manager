<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Backend capability manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_Capability
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_capabilities';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/capability.php';

    /**
     * Capability groups
     *
     * @var array
     *
     * @access public
     * @version 6.0.0
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
            'switch_themes', 'list_users', 'promote_users', 'create_users',
            'delete_site'
        )
    );

    /**
     * Save capability status
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $result = false;
        $cap    = sanitize_text_field($this->getFromPost('capability'));
        $effect = $this->getFromPost('effect', FILTER_VALIDATE_BOOLEAN);
        $assign = $this->getFromPost('assignToMe', FILTER_VALIDATE_BOOLEAN);

        if ($cap && $this->isAllowedToToggle($cap)) {
            // Add capability to current user if checkbox checked
            if ($assign === true) {
                AAM::getUser()->addCapability($cap);
            }

            $result = $this->getSubject()->addCapability($cap, $effect);
        }

        return wp_json_encode(array(
            'status' => ($result ? 'success' : 'failure')
        ));
    }

    /**
     * Update capability slug
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function update()
    {
        $capability = $this->getFromPost('capability');
        $updated    = sanitize_text_field($this->getFromPost('updated'));
        $subject    = $this->getSubject();

        if ($this->isAllowedToEdit($capability) === false) {
            $response = array(
                'status'  => 'failure',
                'message' => __('Permission denied to update this capability', AAM_KEY)
            );
        } else {
            // First we need to get the current grant status for updating capability
            $status = $subject->hasCapability($capability);
            // Remove updating capability
            if ($subject->removeCapability($capability)) {
                // Add new capability with the original grant status
                $result = $subject->addCapability($updated, $status);
            }

            $response = array('status' => (!empty($result) ? 'success' : 'failure'));
        }

        return wp_json_encode($response);
    }

    /**
     * Delete capability
     *
     * This function delete capability in all roles or only for very specific subject.
     * It all depends on the "subjectOnly" POST param.
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function delete()
    {
        $capability  = $this->getFromPost('capability');
        $subjectOnly = $this->getFromPost('subjectOnly', FILTER_VALIDATE_BOOLEAN);

        if ($this->isAllowedToDelete($capability) === false) {
            $response = array(
                'status'  => 'failure',
                'message' => __('Permission denied to delete this capability', AAM_KEY)
            );
        } else {
            if ($subjectOnly === true) {
                $this->getSubject()->removeCapability($capability);
            } else {
                $roles = AAM_Core_API::getRoles();
                foreach (array_keys($roles->roles) as $roleId) {
                    $roles->remove_cap($roleId, $capability);
                }
            }
            $response = array('status' => 'success');
        }

        return wp_json_encode($response);
    }

    /**
     * Get list of capabilities for table view
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getTable()
    {
        $data = array();

        // Compile the complete list of capabilities
        $caps = AAM_Core_API::getAllCapabilities();

        // Add also subject specific capabilities
        $caps = array_merge($caps, $this->getSubject()->getCapabilities());

        foreach (array_keys($caps) as $cap) {
            if (apply_filters('aam_cap_can_filter', true, $cap, 'list') !== false) {
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
     * @version 6.0.0
     */
    protected function prepareActionList($cap)
    {
        $actions = array();
        $subject = $this->getSubject();

        $toggle  = ($subject->hasCapability($cap) ? 'checked' : 'unchecked');

        if ($this->isAllowedToToggle($cap) === false) {
            $toggle = 'no-' . $toggle;
        }

        $actions[] = $toggle;

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
     * Check if current user is allowed to toggle capability
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isAllowedToToggle($cap)
    {
        return apply_filters('aam_cap_can_filter', true, $cap, 'toggle');
    }

    /**
     * Check if current user can edit capability
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isAllowedToEdit($cap)
    {
        $allowed = false;

        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        if (apply_filters('aam_cap_can_filter', true, $cap, 'update') === false) {
            $allowed = false;
        }

        // Check if current subject contains the capability and if so, allow to
        // edit it
        if ($allowed) {
            $allowed = array_key_exists($cap, $this->getSubject()->getCapabilities());
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
     * @version 6.0.0
     */
    protected function isAllowedToDelete($cap)
    {
        $allowed = false;

        if (AAM_Core_Config::get('core.settings.editCapabilities', true)) {
            $allowed = true;
        }

        // Access & Security policy has higher priority
        if (apply_filters('aam_cap_can_filter', true, $cap, 'delete') === false) {
            $allowed = false;
        }

        // Check if current subject contains the capability and if so, allow to
        // delete it
        if ($allowed) {
            $allowed = array_key_exists($cap, $this->getSubject()->getCapabilities());
        }

        return $allowed;
    }

    /**
     * Get capability group list
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getGroupList()
    {
        return apply_filters('aam_capability_groups_filter', array(
            __('System', AAM_KEY),
            __('Posts & Pages', AAM_KEY),
            __('Backend', AAM_KEY),
            __('AAM Interface', AAM_KEY),
            __('Miscellaneous', AAM_KEY)
        ));
    }

    /**
     * Get capability group name
     *
     * @param string $capability
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getGroup($capability)
    {
        if (in_array($capability, self::$groups['system'], true)) {
            $response = __('System', AAM_KEY);
        } elseif (in_array($capability, self::$groups['post'], true)) {
            $response = __('Posts & Pages', AAM_KEY);
        } elseif (in_array($capability, self::$groups['backend'], true)) {
            $response = __('Backend', AAM_KEY);
        } elseif (strpos($capability, 'aam_') === 0) {
            $response = __('AAM Interface', AAM_KEY);
        } else {
            $response = __('Miscellaneous', AAM_KEY);
        }

        return apply_filters('aam_capability_group_filter', $response, $capability);
    }

    /**
     * Register Capability service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'capability',
            'position'   => 15,
            'title'      => __('Capabilities', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID
            ),
            'view'       => __CLASS__
        ));
    }

}