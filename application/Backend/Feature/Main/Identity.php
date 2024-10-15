<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & Roles (aka Identity) Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Main_Identity extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the feature
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_identities';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'service/identity.php';

    /**
     * Get list of allowed user manager rule types
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    protected function get_allowed_identity_types()
    {
        return apply_filters('aam_ui_allowed_identity_types_filter', [
            'role'       => __('Roles', AAM_KEY),
            'role_level' => __('Role level (level_n capability)', AAM_KEY),
            'user_role'  => __('Users that have certain role(s)', AAM_KEY),
            'user'       => __('Users', AAM_KEY),
            'user_level' => __('User level (level_n capability)', AAM_KEY),
        ]);
    }

    /**
     * Return an array of all permissions with proper settings
     *
     * @return array
     *
     * @access protected
     * @version 7.0.0
     */
    protected function get_permission_list()
    {
        $response     = [];
        $access_level = AAM_Backend_AccessLevel::getInstance();

        // List roles can be used for any access level
        $response['list_role'] = [
            'identity_types' => ['role', 'role_level'],
            'hint'           => __('If denies, selected roles are marked as not editable and will not be visible anywhere on the website for %s.', AAM_KEY),
            'title'          => __('LIST ROLE', AAM_KEY)
        ];

        // List users can be used for any access level
        $response['list_user'] = [
            'identity_types' => ['user_role', 'user', 'user_level'],
            'hint'           => __('If denies, all users, that at least one selected role, will be not visible anywhere on the website for %s.', AAM_KEY),
            'title'          => __('LIST USER', AAM_KEY)
        ];

        // Edit users control is accessable only to users can have the ability to edit
        // users
        if ($access_level->has_cap('edit_users')) {
            $response['edit_user'] = [
                'identity_types' => ['user_role', 'user', 'user_level'],
                'hint'           => __('If denies, all users, that match defined criteria above, cannot be edited by %s.', AAM_KEY),
                'title'          => __('EDIT USER', AAM_KEY)
            ];
        }

        // If current user is allowed to manage other users' password, add this control
        // to the list
        if (AAM_Framework_Manager::capabilities()->exists('aam_change_passwords')) {
            $can_change_password = $access_level->has_cap('aam_change_passwords');
        } else {
            $can_change_password = $access_level->has_cap('edit_users');
        }

        if ($can_change_password) {
            $response['change_user_password'] = [
                'identity_types' => ['user_role', 'user', 'user_level'],
                'hint'           => __('If denies, all users, that match defined criteria above, will not be allowed to change or reset password by %s', AAM_KEY),
                'title'          => __('CHANGE USER PASSWORD', AAM_KEY)
            ];
        }

        // If current user is allowed to promote other users, add this control to
        // the list
        if ($access_level->has_cap('promote_users')) {
            $response['change_user_role'] = [
                'identity_types' => ['user_role', 'user', 'user_level'],
                'hint'           => __('If denies, all users, that match defined criteria above, will not be allowed to change a role by %s', AAM_KEY),
                'title'          => __('CHANGE USER ROLE', AAM_KEY)
            ];
        }

        // If current user is allowed to delete other users, add this control to the
        // list
        if ($access_level->has_cap('delete_users')) {
            $response['delete_user'] = [
                'identity_types' => ['user_role', 'user', 'user_level'],
                'hint'           => __('If denies, all users, that match defined criteria above, cannot be deleted by %s', AAM_KEY),
                'title'          => 'DELETE USER'
            ];
        }

        return apply_filters('aam_ui_identity_permission_list_filter', $response);
    }

    /**
     * Register service UI
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'identity',
            'position'   => 60,
            'title'      => __('Identity Governance', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'view'       => __CLASS__
        ));
    }

}