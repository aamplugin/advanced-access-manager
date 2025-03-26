<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check for the elevated number of high privilege users
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Audit_HighPrivilegeUserCountCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 7.0.0
     */
    const ID = 'high_privilege_users_count';

    /**
     * List of core capabilities that can cause damage to the site through content
     *
     * @version 7.0.0
     */
    const CONTENT_HIGH_CAPS = [
        'manage_categories',
        'unfiltered_html',
        'edit_published_pages',
        'delete_published_pages',
        'unfiltered_upload'
    ];

    /**
     * List of core capabilities that can cause damage to the site
     *
     * @version 7.0.0
     */
    const SITE_HIGH_CAPS = [
        'edit_themes',
        'edit_plugins',
        'edit_files',
        'activate_plugins',
        'manage_options',
        'delete_users',
        'create_users',
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
            // Step #1. Identifying the list of roles that have high privileges
            $identified_roles = self::_get_high_privilege_roles(
                self::_read_role_key_option()
            );

            // Scan for high privilege roles
            array_push(
                $issues,
                ...self::_identify_elevated_user_count($identified_roles)
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
            'ELEVATED_ADMIN_USER_COUNT' => __(
                'Detected elevated user count (%d) with admin level privileges',
                'advanced-access-manager'
            ),
            'ELEVATED_EDITOR_USER_COUNT' => __(
                'Detected elevated user count (%d) with high content moderation privileges',
                'advanced-access-manager'
            )
        ];
    }

    /**
     * Identify the list of roles that have high privilege caps
     *
     * @param array $db_roles
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private static function _get_high_privilege_roles($db_roles)
    {
        $response = [
            'content' => [],
            'website' => []
        ];

        foreach($db_roles as $role_id => $role) {
            $assigned_caps = array_keys(
                array_filter($role['capabilities'], function($v) {
                    return !empty($v);
                })
            );

            $content = array_intersect($assigned_caps, self::CONTENT_HIGH_CAPS);
            $website = array_intersect($assigned_caps, self::SITE_HIGH_CAPS);

            if (!empty($content) && $role_id !== 'administrator') {
                array_push($response['content'], $role_id);
            }

            if (!empty($website)) {
                array_push($response['website'], $role_id);
            }
        }

        return $response;
    }

    /**
     * Identify the elevated number of high-privilege users
     *
     * @param array $elevated_roles
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _identify_elevated_user_count($elevated_roles)
    {
        global $wpdb;

        $issues = [];
        $users  = count_users();
        $sums   = [
            'content' => 0,
            'website' => 0
        ];

        foreach($users['avail_roles'] as $role_id => $count) {
            if (in_array($role_id, $elevated_roles['content'], true)) {
                $sums['content'] += $count;
            }

            if (in_array($role_id, $elevated_roles['website'], true)) {
                $sums['website'] += $count;
            }
        }

        $suggested_admins = AAM::api()->config->get(
            'service.security_audit.recommended_admins_count', 1
        );

        // Let's calculate the recommended number of editors based on the number of
        // published posts
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'"
        );

        // Let's be real here. How many articles one senior editor can review per
        // biz day? Let's put an arbitrary number 4 with 250 working days, it may be
        // max 1,000 articles per year.
        $suggested_editors = AAM::api()->config->get(
            'service.security_audit.recommended_editors_count', ceil($total / 1000)
        );

        if ($sums['website'] > $suggested_admins) {
            array_push($issues, self::_format_issue(
                'ELEVATED_ADMIN_USER_COUNT',
                [
                    'user_count' => $sums['website']
                ],
                'critical'
            ));
        }

        if ($sums['content'] > $suggested_editors) {
            array_push($issues, self::_format_issue(
                'ELEVATED_EDITOR_USER_COUNT',
                [
                    'user_count' => $sums['content']
                ],
                'warning'
            ));
        }

        return $issues;
    }

}