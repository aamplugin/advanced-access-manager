<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Detect empty roles
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_EmptyUnusedRoleCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'empty_unused_roles_detection';

    /**
     * List of WordPress core roles
     *
     * @return array
     *
     * @version 7.0.0
     */
    const CORE_ROLES = [
        'administrator',
        'editor',
        'author',
        'contributor',
        'subscriber'
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
            array_push(
                $issues,
                ...self::_detect_empty_roles(self::_read_role_key_option())
            );

            array_push(
                $issues,
                ...self::_detect_unused_roles(self::_read_role_key_option())
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
            'EMPTY_ROLE' => __(
                'Detected role %s (%s) with no capabilities',
                'advanced-access-manager'
            ),
            'UNUSED_CUSTOM_ROLE' => __(
                'Detected unused custom role %s (%s)',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Detect empty roles
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
    private static function _detect_empty_roles($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            if (empty($role['capabilities'])) {
                array_push($response, self::_format_issue(
                    'EMPTY_ROLE',
                    [
                        'name' => translate_user_role(
                            !empty($role['name']) ? $role['name'] : $role_id
                        ),
                        'slug' => $role_id
                    ]
                ));
            }
        }

        return $response;
    }

    /**
     * Detect unused roles
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
    private static function _detect_unused_roles($db_roles)
    {
        $response = [];
        $stats    = count_users();

        foreach($db_roles as $role_id => $role) {
            if (empty($stats['avail_roles'][$role_id])
                && !in_array($role_id, self::CORE_ROLES, true)
            ) {
                array_push($response, self::_format_issue(
                    'UNUSED_CUSTOM_ROLE',
                    [
                        'name' => translate_user_role(
                            !empty($role['name']) ? $role['name'] : $role_id
                        ),
                        'slug' => $role_id
                    ]
                ));
            }
        }

        return $response;
    }

}