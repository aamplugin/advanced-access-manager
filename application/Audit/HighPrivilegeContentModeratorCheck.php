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
 * @version 6.9.43
 */
class AAM_Audit_HighPrivilegeContentModeratorCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * List of roles that are allowed to be high-privileged
     *
     * @version 6.9.43
     */
    const WHITELISTED_ROLES = [
        'administrator', 'editor'
    ];

    /**
     * List of core capabilities that can cause damage to the site's content
     *
     * @version 6.9.43
     */
    const HIGH_PRIVILEGE_CAPS = [
        'manage_categories',
        'unfiltered_html',
        'edit_published_pages',
        'delete_published_pages',
        'unfiltered_upload',
        'unfiltered_html'
    ];

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     * @version 6.9.43
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
            array_push($issues, self::_format_issue(sprintf(
                __('Unexpected application error: %s', AAM_KEY),
                $e->getMessage()
            ), 'APPLICATION_ERROR', 'error'));
        }

        if (count($issues) > 0) {
            $response['issues'] = $issues;
        }

        // Determine final status for the check
        self::_determine_check_status($response);

        return $response;
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
     * @version 6.9.43
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
                    array_push($response, self::_format_issue(sprintf(
                        __('Detected high-privilege content moderator role "%s" with capabilities: %s', AAM_KEY),
                        translate_user_role(
                            !empty($role['name']) ? $role['name'] : $role_id
                        ),
                        implode(', ', $matched)
                    ), 'HIGH_CONTENT_MODERATION_ROLE_CAP', 'critical'));
                }
            }
        }

        return $response;
    }

}