<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & Roles Governance service
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Backend_Feature_Main_IdentityGovernance
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the feature
     *
     * @version 6.9.28
     */
    const ACCESS_CAPABILITY = 'aam_manage_user_governance';

    /**
     * Type of AAM core object
     *
     * @version 6.9.28
     */
    const OBJECT_TYPE = AAM_Core_Object_IdentityGovernance::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.9.28
     */
    const TEMPLATE = 'service/user-governance.php';

    /**
     * Get list of allowed user manager rule types
     *
     * @return array
     *
     * @access protected
     * @version 6.9.28
     */
    protected function get_allowed_rule_types()
    {
        return apply_filters('aam_allowed_user_governance_rule_types_filter', array(
            'role'       => __('Roles', AAM_KEY),
            'role_level' => __('Roles by highest level_n capability', AAM_KEY),
            'user_role'  => __('Users that have certain role', AAM_KEY),
            'user'       => __('Users', AAM_KEY),
            'user_level' => __('Users by highest level_n capability', AAM_KEY),
        ));
    }

    /**
     * Return an array of all permissions with proper settings
     *
     * @return array
     *
     * @access protected
     * @version 6.9.28
     */
    protected function get_permission_list()
    {
        // Determine current access level ability to manage users
        $access_level = AAM_Backend_Subject::getInstance();
        $can_edit     = $access_level->hasCapability('edit_users');

        if (AAM_Core_API::capExists('aam_change_passwords')) {
            $can_change_password = $access_level->hasCapability('aam_change_passwords');
        } else {
            $can_change_password = $access_level->hasCapability('edit_users');
        }

        $can_delete  = $access_level->hasCapability('delete_users');
        $can_promote = $access_level->hasCapability('promote_users');

        return [
            'list_role' => [
                'rule_types' => ['role', 'role_level'],
                'hint'       => __('If denies, selected roles are marked as not editable and will not be visible anywhere on the website for %s.', AAM_KEY),
                'title'      => 'LIST ROLE',
                'disabled'   => false
            ],
            'list_user' => [
                'rule_types' => ['user_role', 'user', 'user_level'],
                'hint'       => __('If denies, all users, that at least one selected role, will be not visible anywhere on the website for %s.', AAM_KEY),
                'title'      => 'LIST USER',
                'disabled'   => false
            ],
            'edit_user' => [
                'rule_types' => ['user_role', 'user', 'user_level'],
                'hint'       => __('If denies, all users, that match defined criteria above, cannot be edited by %s.', AAM_KEY),
                'title'      => 'EDIT USER',
                'disabled'   => !$can_edit
            ],
            'change_user_password' => [
                'rule_types' => ['user_role', 'user', 'user_level'],
                'hint'       => __('If denies, all users, that match defined criteria above, will not be allowed to change or reset password by %s', AAM_KEY),
                'title'      => 'CHANGE USER PASSWORD',
                'disabled'   => !$can_change_password
            ],
            'change_user_role' => [
                'rule_types' => ['user_role', 'user', 'user_level'],
                'hint'       => __('If denies, all users, that match defined criteria above, will not be allowed to change a role by %s', AAM_KEY),
                'title'      => 'CHANGE USER ROLE',
                'disabled'   => !$can_promote
            ],
            'delete_user' => [
                'rule_types' => ['user_role', 'user', 'user_level'],
                'hint'       => __('If denies, all users, that match defined criteria above, cannot be deleted by %s', AAM_KEY),
                'title'      => 'DELETE USER',
                'disabled'   => !$can_delete
            ]
        ];
    }

    /**
     * Register service UI
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'user-governance',
            'position'   => 60,
            'title'      => __('Users & Roles Governance', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}