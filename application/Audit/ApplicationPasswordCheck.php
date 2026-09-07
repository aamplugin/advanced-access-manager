<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check how many users have application passwords enabled
 *
 * @package AAM
 * @version 7.1.3
 */
class AAM_Audit_ApplicationPasswordCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.1.3
     */
    const ID = 'application_passwords';

    /**
     * Run the check
     *
     * @return array
     *
     * @access public
     * @static
     *
     * @version 7.1.3
     */
    public static function run()
    {
        $issues   = [];
        $response = [ 'is_completed' => true ];

        try {
            array_push(
                $issues,
                ...self::_check_application_passwords()
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
     * @version 7.1.3
     */
    private static function _get_message_templates()
    {
        return [
            'HIDDEN_ROLES' => __(
                'Detected user %s (ID %d) with active application password',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Check for users that have application passwords enabled
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _check_application_passwords()
    {
        $response = [];

        // Get list of all user IDs and names have have _application_passwords meta
        $users = get_users([
            'meta_key' => WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
            'meta_compare' => 'EXISTS',
            'fields' => [ 'ID', 'display_name' ]
        ]);

        foreach ($users as $user) {
            $user_id   = $user->ID;
            $user_name = $user->display_name;

            array_push($response, self::_format_issue(
                'HIDDEN_ROLES',
                [
                    'name' => $user_name,
                    'id' => $user_id
                ]
            ));
        }

        return $response;
    }

}