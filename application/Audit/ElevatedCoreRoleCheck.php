<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Detect WordPress core roles that got elevated access
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_ElevatedCoreRoleCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'elevated_core_role_caps';

    /**
     * WordPress core roles to scan
     *
     * @version 7.0.0
     */
    const TARGETING_CORE_ROLES = [
        'editor' => [
            'name'         => 'Editor',
            'capabilities' => [
                'moderate_comments',
                'manage_categories',
                'manage_links',
                'upload_files',
                'unfiltered_html',
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

            array_push(
                $issues,
                ...self::_detect_elevated_core_roles($db_roles)
            );
        } catch (Exception $e) {
            array_push($failure, self::_format_issue(
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
            'ELEVATED_CORE_ROLE_CAPS' => __(
                'Detected WordPress core role %s (%s) with elevated caps: %s',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Detect WordPress core roles that have elevated access controls
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
    private static function _detect_elevated_core_roles($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            // Take into consideration only core roles
            if (array_key_exists($role_id, self::TARGETING_CORE_ROLES)) {
                $existing_caps = array_keys(
                    array_filter($role['capabilities'], function($v) {
                        return !empty($v);
                    })
                );
                $diff_caps = array_diff(
                    $existing_caps,
                    self::TARGETING_CORE_ROLES[$role_id]['capabilities']
                );

                if (!empty($diff_caps)) {
                    array_push($response, self::_format_issue(
                        'ELEVATED_CORE_ROLE_CAPS',
                        [
                            'name' => translate_user_role($role['name']),
                            'slug' => $role_id,
                            'caps' => $diff_caps
                        ],
                        'warning'
                    ));
                }
            }
        }

        return $response;
    }

}