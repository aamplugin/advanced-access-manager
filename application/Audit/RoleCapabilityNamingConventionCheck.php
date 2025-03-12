<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check if roles and capabilities follow proper naming convention
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_RoleCapabilityNamingConventionCheck
{

    use AAM_Audit_AuditCheckTrait;

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
                ...self::_validate_naming_convention(self::_read_role_key_option())
            );
        } catch (Exception $e) {
            array_push($issues, self::_format_issue(sprintf(
                __('Unexpected application error: %s', 'advanced-access-manager'),
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
     * Validate that all roles and capabilities are following proper naming
     * convention
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
    private static function _validate_naming_convention($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            if (preg_match('/^[a-z\d_\-]+$/', $role_id) !== 1) {
                array_push($response, self::_format_issue(sprintf(
                    __('Detected role "%s" with incorrect identifier', 'advanced-access-manager'),
                    $role_id
                ), 'INCORRECT_ROLE_SLUG'));
            }

            foreach(array_keys($role['capabilities']) as $cap) {
                if (preg_match('/^[a-z\d_\-]+$/', $cap) !== 1) {
                    array_push($response, self::_format_issue(sprintf(
                        __('Detected incorrect capability "%s" for %s role', 'advanced-access-manager'),
                        $cap,
                        $role_id
                    ), 'INCORRECT_CAP_SLUG'));
                }
            }
        }

        return $response;
    }

}