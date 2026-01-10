<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role integrity audit check
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_RoleIntegrityCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'core_roles_integrity';

    /**
     * Default collection of roles & capabilities
     *
     * @version 7.0.0
     */
    const DEFAULT_STATE = [
        'administrator' => [
            'name'         => 'Administrator',
            'capabilities' => [
                'switch_themes',
                'edit_themes',
                'activate_plugins',
                'edit_plugins',
                'edit_users',
                'edit_files',
                'manage_options',
                'moderate_comments',
                'manage_categories',
                'manage_links',
                'upload_files',
                'import',
                'unfiltered_html',
                'edit_posts',
                'edit_others_posts',
                'edit_published_posts',
                'publish_posts',
                'edit_pages',
                'read',
                'level_10',
                'level_9',
                'level_8',
                'level_7',
                'level_6',
                'level_5',
                'level_4',
                'level_3',
                'level_2',
                'level_1',
                'level_0',
                'edit_others_pages',
                'edit_published_pages',
                'publish_pages',
                'delete_pages',
                'delete_others_pages',
                'delete_published_pages',
                'delete_posts',
                'delete_others_posts',
                'delete_published_posts',
                'delete_private_posts',
                'edit_private_posts',
                'read_private_posts',
                'delete_private_pages',
                'edit_private_pages',
                'read_private_pages',
                'delete_users',
                'create_users',
                'unfiltered_upload',
                'edit_dashboard',
                'update_plugins',
                'delete_plugins',
                'install_plugins',
                'update_themes',
                'install_themes',
                'update_core',
                'list_users',
                'remove_users',
                'promote_users',
                'edit_theme_options',
                'delete_themes',
                'export'
            ]
        ],
        'editor' => [
            'name'         => 'Editor',
            'capabilities' => [
                'moderate_comments',
                'manage_categories',
                'manage_links',
                'upload_files',
                // 'unfiltered_html', - Disabling this as it is very dangerous cap
                'edit_posts',
                'edit_others_posts',
                'edit_published_posts',
                'publish_posts',
                'edit_pages',
                'read',
                'level_7',
                'level_6',
                'level_5',
                'level_4',
                'level_3',
                'level_2',
                'level_1',
                'level_0',
                'edit_others_pages',
                'edit_published_pages',
                'publish_pages',
                'delete_pages',
                'delete_others_pages',
                'delete_published_pages',
                'delete_posts',
                'delete_others_posts',
                'delete_published_posts',
                'delete_private_posts',
                'edit_private_posts',
                'read_private_posts',
                'delete_private_pages',
                'edit_private_pages',
                'read_private_pages'
            ]
        ],
        'author' => [
            'name'         => 'Author',
            'capabilities' => [
                'upload_files',
                'edit_posts',
                'edit_published_posts',
                'publish_posts',
                'read',
                'level_2',
                'level_1',
                'level_0',
                'delete_posts',
                'delete_published_posts'
            ]
        ],
        'contributor' => [
            'name'         => 'Contributor',
            'capabilities' => [
                'edit_posts',
                'read',
                'level_1',
                'level_0',
                'delete_posts'
            ]
        ],
        'subscriber' => [
            'name'         => 'Subscriber',
            'capabilities' => [
                'read',
                'level_0'
            ]
        ]
    ];

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function run()
    {
        $issues   = [];
        $response = [ 'is_completed' => true ];

        try {
            $db_roles = self::_read_role_key_option();

            // Step #1. Insuring all WordPress core roles exist
            array_push($issues, ...self::_validate_core_role_existence($db_roles));

            // Step #2. Insuring all WordPress core roles have necessary caps
            array_push(
                $issues,
                ...self::_validate_core_role_capabilities($db_roles)
            );
        } catch (Exception $e) {
            array_push($issues, self::_format_issue(
                'APPLICATION_ERROR',
                [
                    'message' => $e->getMessage()
                ],
                'error'
            ));
        }

        if (count($issues) > 0) {
            $response['issues'] = $issues;
        }

        // Determine final status for the check
        self::_determine_check_status($response);

        return $response;
    }

    /**
     * Get a collection of error messages for current step
     *
     * @return array
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _get_message_templates()
    {
        return [
            'MISSING_CORE_ROLES' => __(
                'Detected missing WordPress core role(s): %s',
                'advanced-access-manager'
            ),
            'MISSING_CORE_CAPS' => __(
                'Detected missing WordPress core caps for role %s (%s): %s',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Validate that core WP roles exist
     *
     * @param array $db_roles
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _validate_core_role_existence($db_roles)
    {
        $response = [];

        $existing_roles = array_keys($db_roles);
        $complete_roles = array_keys(self::DEFAULT_STATE);
        $diff_roles     = array_diff($complete_roles, $existing_roles);

        if (!empty($diff_roles)) {
            array_push($response, self::_format_issue(
                'MISSING_CORE_ROLES',
                [
                    'roles' => $diff_roles
                ],
                'warning'
            ));
        }

        return $response;
    }

    /**
     * Validate that WP core capabilities assigned to core roles
     *
     * @param array $db_roles
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _validate_core_role_capabilities($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            // Take into consideration only core roles
            if (array_key_exists($role_id, self::DEFAULT_STATE)) {
                $existing_caps = array_keys(
                    array_filter($role['capabilities'], function($v) {
                        return !empty($v);
                    })
                );
                $complete_caps = self::DEFAULT_STATE[$role_id]['capabilities'];
                $diff_caps     = array_diff($complete_caps, $existing_caps);

                if (!empty($diff_caps)) {
                    array_push($response, self::_format_issue(
                        'MISSING_CORE_CAPS',
                        [
                            'role_name' => translate_user_role($role['name']),
                            'role_slug' => $role_id,
                            'caps'      => $diff_caps
                        ],
                        'warning'
                    ));
                }
            }
        }

        return $response;
    }

}