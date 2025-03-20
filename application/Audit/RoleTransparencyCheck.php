<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check if all registered roles are transparent to the admin user
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_RoleTransparencyCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'roles_visibility';

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
            array_push(
                $issues,
                ...self::_validate_roles_transparency(self::_read_role_key_option())
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
            'HIDDEN_ROLES' => __(
                'Detected hidden role(s): %s',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Validate all registered roles are visible to the admin user
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
    private static function _validate_roles_transparency($db_roles)
    {
        $response = [];

        $registered_roles = array_keys($db_roles);

        if (function_exists('get_editable_roles')) {
            $visible_roles = array_keys(get_editable_roles());
        } else {
            $visible_roles = array_keys(
                apply_filters('editable_roles', wp_roles()->roles)
            );
        }

        // Compute the difference
        $diff_roles = array_diff($registered_roles, $visible_roles);

        if (!empty($diff_roles)) {
            array_push($response, self::_format_issue(
                'HIDDEN_ROLES',
                [
                    'roles' => $diff_roles
                ]
            ));
        }

        return $response;
    }

}