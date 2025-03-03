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
 * @since 6.9.47 https://github.com/aamplugin/advanced-access-manager/issues/433
 * @since 6.9.40 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.47
 */
class AAM_Audit_RoleTransparencyCheck
{

    use AAM_Audit_AuditCheckTrait;

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
            array_push(
                $issues,
                ...self::_validate_roles_transparency(self::_read_role_key_option())
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
     * Validate all registered roles are visible to the admin user
     *
     * @param array $db_roles
     *
     * @return array
     *
     * @since 6.9.47 https://github.com/aamplugin/advanced-access-manager/issues/433
     * @since 6.9.40 Initial implementation of the method
     *
     * @access private
     * @static
     * @version 6.9.47
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
            array_push($response, self::_format_issue(sprintf(
                __('Detected hidden role(s): %s', AAM_KEY),
                implode(', ', $diff_roles)
            ), 'HIDDEN_ROLE'));
        }

        return $response;
    }

}