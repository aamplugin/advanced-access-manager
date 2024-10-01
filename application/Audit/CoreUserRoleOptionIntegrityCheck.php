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
 * @version 6.9.40
 */
class AAM_Audit_CoreUserRoleOptionIntegrityCheck
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
        $issues = [];
        $response = [ 'is_completed' => true ];

        try {
            $db_roles = self::_read_role_key_option();

            // The core user_roles structure is intact and not deviated from
            // th WordPress core original standard
            array_push(
                $issues,
                ...self::_validate_core_option_structure($db_roles)
            );
        } catch (Exception $e) {
            array_push($issues, self::_format_issue(sprintf(
                __('Unexpected application error: %s', AAM_KEY),
                $e->getMessage()
            ), 'error'));
        }

        if (count($issues) > 0) {
            $response['issues'] = $issues;
        }

        // Determine final status for the check
        self::_determine_check_status($response);

        return $response;
    }

    /**
     * Validate WordPress core option _user_roles
     *
     * @param array $db_roles
     *
     * @return array
     *
     * @access private
     * @static
     * @version 6.9.40
     */
    private static function _validate_core_option_structure($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            // Step #1. Validating that all the keys are strings
            if (!is_string($role_id)) {
                array_push($response, self::_format_issue(sprintf(
                    __('Detected role "%s" with invalid identifier', AAM_KEY),
                    $role_id
                ), 'warning'));
            }

            // Step #2. Verifying that each role has only proper properties & no core
            // props are missing
            $props         = array_keys($role);
            $invalid_props = array_diff($props, ['name', 'capabilities']);
            $missing_props = array_diff(['name', 'capabilities'], $props);

            if (!empty($invalid_props)) {
                array_push($response, self::_format_issue(sprintf(
                    __('Detected role "%s" with invalid properties: %s', AAM_KEY),
                    $role_id,
                    implode(', ', $invalid_props)
                )));
            }

            if (!empty($missing_props)) {
                array_push($response, self::_format_issue(sprintf(
                    __('Detected role "%s" with missing mandatory properties: %s', AAM_KEY),
                    $role_id,
                    implode(', ', $missing_props)
                ), 'critical'));
            }
        }

        return $response;
    }

}