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
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'roles_caps_naming_convention';

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
            'INVALID_CAP_SLUG' => __(
                'Detected invalid capability %s for %s (%s) role',
                'advanced-access-manager'
            )
        ];
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
                array_push($response, self::_format_issue(
                    'INVALID_ROLE_SLUG',
                    [
                        'name' => translate_user_role(
                            !empty($role['name']) ? $role['name'] : $role_id
                        ),
                        'slug' => $role_id
                    ]
                ));
            }

            foreach(array_keys($role['capabilities']) as $cap) {
                if (preg_match('/^[a-z\d_\-]+$/', $cap) !== 1) {
                    array_push($response, self::_format_issue(
                        'INVALID_CAP_SLUG',
                        [
                            'slug'      => $cap,
                            'role_name' => translate_user_role(
                                !empty($role['name']) ? $role['name'] : $role_id
                            ),
                            'role_slug' => $role_id
                        ]
                    ));
                }
            }
        }

        return $response;
    }

}