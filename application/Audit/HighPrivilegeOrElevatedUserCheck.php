<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check for the high privilege users
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_HighPrivilegeOrElevatedUserCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'high_privilege_or_elevated_users';

    /**
     * Maximum number of users to iterate with one execution
     *
     * @version 7.0.0
     */
    const ITERATION_LIMIT = 2000;

    /**
     * Maximum number of iterations before stop
     *
     * This is done to avoid overloading DB with tons of issues
     *
     * @version 7.0.0
     */
    const MAX_ITERATIONS = 5;

    /**
     * List of core capabilities that can cause significant damage to the site
     *
     * @version 7.0.0
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
     * @param array $params
     *
     * @return array
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function run($params = [])
    {
        $issues = [];
        $result = array_merge([
            'is_completed' => false,
            'progress'     => 0,
            'offset'       => 0,
            // Calculate the maximum number of users we can check with this step
            'max'          => AAM::api()->config->get(
                'service.security_audit.max_users_to_check',
                self::ITERATION_LIMIT * self::MAX_ITERATIONS
            )
        ], $params);

        try {
            // Step #1. Let's determine how many users are on the site, but only if
            // it is the first iteration of the check and fetch the batch of users
            // for further processing
            if ($result['progress'] === 0) {
                $user_list = AAM::api()->users->get_users([
                    'number'  => self::ITERATION_LIMIT,
                    'orderby' => 'ID'
                ]);

                // Capture the total number of users
                $result['total_count'] = AAM::api()->users->get_user_count();

                // Determine if total number of users is higher than allowed number
                // of users for check
                $result['has_overflow'] = $result['total_count'] > $result['max'];
            } else {
                $user_list = AAM::api()->users->get_users([
                    'number'  => self::ITERATION_LIMIT,
                    'orderby' => 'ID',
                    'offset'  => $result['offset']
                ]);
            }

            // Increment the offset and move on
            $result['offset'] += self::ITERATION_LIMIT;

            // Step #2. Analyze the batch and determine how many users have high
            // privilege access
            array_push(
                $issues,
                ...self::_scan_for_high_privilege_users($user_list)
            );

            // Step #3. Determining if we actually done with the scan
            if ($result['total_count'] <= $result['offset']
                || ($result['max'] !== -1 && $result['offset'] >= $result['max'])
            ) {
                $result['is_completed'] = true;
            } else {
                $result['progress'] = $result['offset'] / $result['total_count'];
            }
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
            if (array_key_exists('issues', $result)) {
                array_push($result['issues'], ...$issues);
            } else {
                $result['issues'] = $issues;
            }
        }

        // Determine final status for the check
        self::_determine_check_status($result);

        return $result;
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
            'HIGH_PRIVILEGE_CAPS_USER' => __(
                'Detected high-privilege user %s (ID: %d) with caps: %s',
                'advanced-access-manager'
            ),
            'ELEVATED_CAPS_USER' => __(
                'Detected user %s (ID: %d) with elevated caps: %s',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * @inheritDoc
     *
     * Let's not share any information (like IDs or names) about specific user
     * accounts
     *
     * @version 7.0.0
     */
    public static function issues_to_shareable($results)
    {
        $response = [];

        foreach($results['issues'] as $issue) {
            $issue_code = $issue['code'];
            if (!array_key_exists($issue_code, $response)) {
                $response[$issue_code] = [
                    'type'     => $issue['type'],
                    'code'     => $issue_code,
                    'metadata' => [
                        'user_count'   => 0,
                        'capabilities' => []
                    ]
                ];
            }

            $response[$issue_code]['metadata']['user_count']++; // Increment #

            $response[$issue_code]['metadata']['capabilities'] = array_unique(array_merge(
                $response[$issue_code]['metadata']['capabilities'],
                $issue['metadata']['caps']
            ));
        }

        return $response;
    }

    /**
     * Scan for high-privilege users
     *
     * @param array $user_list
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _scan_for_high_privilege_users($user_list)
    {
        $response = [];

        // We are going to exclude the admin of the site
        $admin_email = AAM::api()->db->read('admin_email');

        foreach($user_list as $user) {
            // Exclude current user and assume that they are the only Administrator
            // with high-privilege access
            if ($user->user_email !== $admin_email) {
                $assigned_caps = array_keys(
                    array_filter($user->allcaps, function($v) {
                        return !empty($v);
                    })
                );

                $matched = array_intersect($assigned_caps, self::HIGH_PRIVILEGE_CAPS);

                if (!empty($matched)) {
                    array_push($response, self::_format_issue(
                        'HIGH_PRIVILEGE_CAPS_USER',
                        [
                            'name' => $user->display_name,
                            'id'   => $user->ID,
                            'caps' => $matched
                        ],
                        'critical'
                    ));
                }

                // Detecting if user has elevated privileges as well
                $elevated_caps = array_keys(
                    array_filter($user->caps, function($v, $k) {
                        return !empty($v) && !wp_roles()->is_role($k);
                    }, ARRAY_FILTER_USE_BOTH)
                );

                if (!empty($elevated_caps)) {
                    array_push($response, self::_format_issue(
                        'ELEVATED_CAPS_USER',
                        [
                            'name' => $user->display_name,
                            'id'   => $user->ID,
                            'caps' => $elevated_caps
                        ]
                    ));
                }
            }
        }

        return $response;
    }

}