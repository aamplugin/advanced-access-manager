<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check for the high privilege roles
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_HighPrivilegeContentModeratorCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'high_privilege_content_moderator_roles';

    /**
     * List of roles that are allowed to be high-privileged
     *
     * @version 7.0.0
     */
    const WHITELISTED_ROLES = [
        'administrator',
        'editor'
    ];

    /**
     * List of core capabilities that can cause damage to the site's content
     *
     * @version 7.0.0
     */
    const HIGH_PRIVILEGE_CAPS = [
        'manage_categories',
        'unfiltered_html',
        'edit_published_pages',
        'delete_published_pages',
        'unfiltered_upload'
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
            // Scan for high privilege roles
            array_push(
                $issues,
                ...self::_scan_for_high_privilege_roles(self::_read_role_key_option())
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
            'HIGH_CONTENT_MODERATOR_ROLE' => __(
                'Detected high-privilege content moderator role %s (%s) with caps: %s',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Scan for high-privilege roles that are not whitelisted
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
    private static function _scan_for_high_privilege_roles($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            if (!in_array($role_id, self::WHITELISTED_ROLES)) {
                $assigned_caps = array_keys(
                    array_filter($role['capabilities'], function($v) {
                        return !empty($v);
                    })
                );

                $matched = array_intersect($assigned_caps, self::HIGH_PRIVILEGE_CAPS);

                if (!empty($matched)) {
                    array_push($response, self::_format_issue(
                        'HIGH_CONTENT_MODERATOR_ROLE',
                        [
                            'name' => translate_user_role(
                                !empty($role['name']) ? $role['name'] : $role_id
                            ),
                            'slug' => $role_id,
                            'caps' => $matched
                        ],
                        'critical'
                    ));
                }
            }
        }

        return $response;
    }

}