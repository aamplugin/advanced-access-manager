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
     * Get list of permissions for the Role resource
     *
     * @return array
     * @access protected
     *
     * @version 7.0.0
     */
    protected function get_role_permission_list()
    {
        $access_level = AAM_Backend_AccessLevel::get_instance();

        return [
            'list_role' => [
                'hint'  => sprintf(
                    __('When denies, the selected role is not visible anywhere on the website for %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('LIST ROLE', AAM_KEY)
            ],
            'list_user' => [
                'hint'  => sprintf(
                    __('When denies, users that have the selected role are not visible anywhere on the website for %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('LIST USERS', AAM_KEY)
            ],
            'edit_user' => [
                'hint'  => sprintf(
                    __('When denies, users that have the selected role can not be edited by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('EDIT USERS', AAM_KEY)
            ],
            'promote_user' => [
                'hint'  => sprintf(
                    __('When denies, users that belong to the selected role can not be promoted to any other role by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('PROMOTE USERS', AAM_KEY)
            ],
            'change_user_password' => [
                'hint'  => sprintf(
                    __('When denied, users assigned to the selected role are restricted from having their passwords changed by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('CHANGE USERS PASSWORD', AAM_KEY)
            ],
            'delete_user' => [
                'hint'  => sprintf(
                    __('When denies, users that belong to the selected role can not be deleted by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('DELETE USERS', AAM_KEY)
            ]
        ];
    }

    /**
     * Get list of permissions for the User resource
     *
     * @return array
     * @access protected
     *
     * @version 7.0.0
     */
    protected function get_user_permission_list()
    {
        $access_level = AAM_Backend_AccessLevel::get_instance();

        return [
            'list_user' => [
                'hint'  => sprintf(
                    __('When denies, the selected user is not visible anywhere on the website for %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('LIST USER', AAM_KEY)
            ],
            'edit_user' => [
                'hint'  => sprintf(
                    __('When denies, the selected user can not be edited by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('EDIT USER', AAM_KEY)
            ],
            'promote_user' => [
                'hint'  => sprintf(
                    __('When denies, the selected user can not be promoted to any other role by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('PROMOTE USER', AAM_KEY)
            ],
            'change_user_password' => [
                'hint'  => sprintf(
                    __('When denies, the selected user`s password can not be changed by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('CHANGE USER PASSWORD', AAM_KEY)
            ],
            'delete_user' => [
                'hint'  => sprintf(
                    __('When denies, the selected user can not be deleted by %s.', AAM_KEY),
                    $access_level->get_display_name()
                ),
                'title' => __('DELETE USER', AAM_KEY)
            ]
        ];
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