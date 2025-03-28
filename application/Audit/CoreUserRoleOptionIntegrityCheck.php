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
class AAM_Audit_CoreUserRoleOptionIntegrityCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'user_roles_option_integrity';

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
        $issues = [];
        $response = [ 'is_completed' => true ];

        try {
            $db_roles = self::_read_role_key_option();

            // The core user_roles structure is intact and not deviated from
            // the WordPress core original standard
            array_push(
                $issues,
                ...self::_validate_core_option_structure($db_roles)
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
            'INVALID_ROLE_SLUG' => __(
                'Detected role %s (%s) with invalid slug',
                'advanced-access-manager'
            ),
            'ILLEGAL_ROLE_PROPERTY' => __(
                'Detected role %s (%s) with invalid properties: %s',
                'advanced-access-manager'
            ),
            'MISSING_ROLE_PROPERTY' => __(
                'Detected role %s (%s) with missing mandatory properties: %s',
                'advanced-access-manager'
            )
        ];
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
     *
     * @version 7.0.0
     */
    private static function _validate_core_option_structure($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            // Step #1. Validating that all the keys are strings
            if (!is_string($role_id)) {
                array_push($response, self::_format_issue(
                    'INVALID_ROLE_SLUG',
                    [
                        'name' => isset($role['name']) ? $role['name'] : $role_id,
                        'slug' => $role_id
                    ],
                    'warning'
                ));
            }

            // Step #2. Verifying that each role has only proper properties & no core
            // props are missing
            $props         = array_keys($role);
            $invalid_props = array_diff($props, ['name', 'capabilities']);
            $missing_props = array_diff(['name', 'capabilities'], $props);

            if (!empty($invalid_props)) {
                array_push($response, self::_format_issue(
                    'ILLEGAL_ROLE_PROPERTY',
                    [
                        'name'  => isset($role['name']) ? $role['name'] : $role_id,
                        'slug'  => $role_id,
                        'props' => $invalid_props
                    ]
                ));
            }

            if (!empty($missing_props)) {
                array_push($response, self::_format_issue(
                    'MISSING_ROLE_PROPERTY',
                    [
                        'name'  => isset($role['name']) ? $role['name'] : $role_id,
                        'slug'  => $role_id,
                        'props' => $missing_props
                    ],
                    'critical'
                ));
            }
        }

        return $response;
    }

}