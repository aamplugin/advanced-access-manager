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
 * @version 6.9.40
 */
class AAM_Audit_HighPrivilegeRoleCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * List of roles that are allowed to be high-privileged
     *
     * @version 6.9.40
     */
    const WHITELISTED_ROLES = [
        'administrator'
    ];

    /**
     * List of core capabilities that can cause significant damage to the site
     *
     * @version 6.9.40
     */
    const HIGH_PRIVILEGE_CAPS = [
        'edit_themes',
        'edit_plugins',
        'edit_files',
        'activate_plugins',
        'manage_options',
        'delete_users',
        'create_users',
        'unfiltered_upload',
        'unfiltered_html',
        'update_plugins',
        'delete_plugins',
        'install_plugins',
        'update_themes',
        'install_themes',
        'update_core',
        'promote_users',
        'delete_themes'
    ];

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     * @version 6.9.40
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
     * @version 6.9.40
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
                        __('Detected high-privilege role "%s" with capabilities: %s', AAM_KEY),
                        translate_user_role(
                            !empty($role['name']) ? $role['name'] : $role_id
                        ),
                        implode(', ', $matched)
                    ), 'HIGH_PRIVILEGE_ROLE_CAPS', 'critical'));
                }
            }
        }

        return $response;
    }

}