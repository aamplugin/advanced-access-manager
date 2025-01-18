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
 * @version 6.9.40
 */
class AAM_Audit_EmptyRoleCheck
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
                ...self::_detect_empty_roles(self::_read_role_key_option())
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
     * Detect empty roles
     *
     * @param array $db_roles
     *
     * @return array
     *
     * @access private
     * @static
     * @version 6.9.40
     */
    private static function _detect_empty_roles($db_roles)
    {
        $response = [];

        foreach($db_roles as $role_id => $role) {
            if (empty($role['capabilities'])) {
                array_push($response, self::_format_issue(sprintf(
                    __('Detected role "%s" with no capabilities', AAM_KEY),
                    translate_user_role(
                        !empty($role['name']) ? $role['name'] : $role_id
                    )
                ), 'EMPTY_ROLE'));
            }
        }

        return $response;
    }

}