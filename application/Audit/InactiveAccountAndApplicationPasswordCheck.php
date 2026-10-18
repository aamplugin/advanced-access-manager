<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Check for inactive accounts and stale or aging application passwords
 *
 * @package AAM
 * @version 8.0.2
 */
class AAM_Audit_InactiveAccountAndApplicationPasswordCheck
{

    use AAM_Audit_AuditCheckTrait;

    /**
     * Step ID
     *
     * @version 8.0.2
     */
    const ID = 'inactive_accounts_and_application_passwords';

    /**
     * Number of users scanned in one audit request
     *
     * @version 8.0.2
     */
    const BATCH_SIZE = 2000;

    /**
     * Run one page of the scan
     *
     * @param array $params [Optional]
     *
     * @return array
     * @access public
     * @static
     *
     * @version 8.0.2
     */
    public static function run($params = [])
    {
        $result = array_merge([
            'is_completed' => false,
            'progress'     => 0,
            'offset'       => 0,
            'last_user_id' => 0
        ], $params);

        $issues = [];

        try {
            $batch_size = max(1, min(self::BATCH_SIZE, (int) apply_filters(
                'aam_security_audit_inactive_batch_size_filter', self::BATCH_SIZE
            )));
            $users = self::_get_user_batch(
                max(0, (int) $result['last_user_id']), $batch_size
            );

            if (!isset($result['total_count'])) {
                $result['total_count'] = self::_get_user_count();
            }

            $account_days = self::_get_threshold(
                'service.security_audit.inactive_user_account_threshold'
            );
            $password_days = self::_get_threshold(
                'service.security_audit.inactive_application_password_threshold'
            );
            $rotation_days = self::_get_threshold(
                'service.security_audit.application_password_ttl', 180
            );
            $tracker_started = AAM::api()->config->get(
                'service.secure_login.track_last_access'
            ) ? (int) get_option(
                AAM_Service_SecureLogin::TRACKER_STARTED_OPTION, 0
            ) : 0;
            $now = time();

            foreach ($users as $user) {
                self::_scan_user(
                    $user,
                    $now - $account_days * DAY_IN_SECONDS,
                    $now - $password_days * DAY_IN_SECONDS,
                    $now - $rotation_days * DAY_IN_SECONDS,
                    $tracker_started,
                    $issues
                );
            }

            $result['offset'] += count($users);
            if ($users) {
                $result['last_user_id'] = max(array_keys($users));
            }
            $result['is_completed'] = count($users) < $batch_size
                || $result['offset'] >= $result['total_count'];

            $result['progress'] = $result['is_completed'] ? 1
                : $result['offset'] / max(1, $result['total_count']);
        } catch (Exception $e) {
            $issues[] = self::_format_issue('APPLICATION_ERROR', [
                'message' => $e->getMessage()
            ], 'error');
            $result['is_completed'] = true;
        }

        if ($issues) {
            $result['issues'] = $issues;
        }

        self::_determine_check_status($result);

        return $result;
    }

    /**
     * Fetch one page of users with both metadata values in a single query
     *
     * @param int $last_user_id
     * @param int $batch_size
     *
     * @return array
     * @access private
     * @static
     *
     * @version 8.0.2
     */
    private static function _get_user_batch($last_user_id, $batch_size)
    {
        global $wpdb;

        $membership_key = $wpdb->get_blog_prefix(get_current_blog_id())
            . 'capabilities';

        $query = $wpdb->prepare(
            "SELECT batch.ID, batch.display_name, batch.user_registered,
                access.meta_value AS last_access,
                passwords.meta_value AS application_passwords
            FROM (
                SELECT users.ID, users.display_name, users.user_registered
                FROM {$wpdb->users} AS users
                WHERE users.ID > %d AND EXISTS (
                    SELECT 1 FROM {$wpdb->usermeta} AS membership
                    WHERE membership.user_id = users.ID
                        AND membership.meta_key = %s
                )
                ORDER BY users.ID ASC LIMIT %d
            ) AS batch
            LEFT JOIN {$wpdb->usermeta} AS access
                ON access.user_id = batch.ID AND access.meta_key = %s
            LEFT JOIN {$wpdb->usermeta} AS passwords
                ON passwords.user_id = batch.ID AND passwords.meta_key = %s
            ORDER BY batch.ID ASC, access.umeta_id ASC, passwords.umeta_id ASC",
            $last_user_id,
            $membership_key,
            $batch_size,
            AAM_Service_SecureLogin::LAST_ACCESS_TIME_META,
            WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS
        );
        $rows = $wpdb->get_results($query);

        if ($rows === null) {
            throw new RuntimeException($wpdb->last_error);
        }

        $users = [];
        foreach ($rows as $row) {
            // WordPress normally keeps one row per key. If duplicates exist,
            // prefer the most recently inserted metadata row.
            $users[(int) $row->ID] = $row;
        }

        return $users;
    }

    /**
     * Count users who belong to the current site
     *
     * @return int
     * @access private
     * @static
     *
     * @version 8.0.2
     */
    private static function _get_user_count()
    {
        global $wpdb;

        $membership_key = $wpdb->get_blog_prefix(get_current_blog_id())
            . 'capabilities';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} AS users WHERE EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} AS membership
                WHERE membership.user_id = users.ID
                    AND membership.meta_key = %s
            )",
            $membership_key
        ));
    }

    /**
     * Remove account and credential identifiers from shareable reports
     *
     * @param array $results
     *
     * @return array
     * @access public
     * @static
     *
     * @version 8.0.2
     */
    public static function issues_to_shareable($results)
    {
        $summary = [];

        foreach ($results['issues'] as $issue) {
            $code = $issue['code'];
            if (!isset($summary[$code])) {
                $summary[$code] = [
                    'code'     => $code,
                    'type'     => $issue['type'],
                    'metadata' => [ 'count' => 0 ]
                ];
            }
            $summary[$code]['metadata']['count']++;
        }

        return array_values($summary);
    }

    /**
     * Read a positive threshold in days
     *
     * @param string $key
     * @param int    $default [Optional]
     *
     * @return int
     * @access private
     * @static
     *
     * @version 8.0.2
     */
    private static function _get_threshold($key, $default = 60)
    {
        $days = (int) AAM::api()->config->get($key, $default);

        return $days > 0 ? $days : $default;
    }

    /**
     * Inspect one account and its application passwords
     *
     * @param object $user
     * @param int    $account_cutoff
     * @param int    $password_cutoff
     * @param int    $rotation_cutoff
     * @param int    $tracker_started
     * @param array  $issues
     *
     * @return void
     * @access private
     * @static
     *
     * @version 8.0.2
     */
    private static function _scan_user(
        $user, $account_cutoff, $password_cutoff, $rotation_cutoff,
        $tracker_started, &$issues
    )
    {
        $id = (int) $user->ID;
        $name = $user->display_name;
        $passwords = maybe_unserialize($user->application_passwords);
        $passwords = is_array($passwords) ? $passwords : [];
        $last_password_use = 0;

        foreach ($passwords as $index => $password) {
            if (!is_array($password)) {
                continue;
            }

            $last_used = (int) ($password['last_used'] ?? 0);
            $last_password_use = max($last_password_use, $last_used);
            $created = (int) ($password['created'] ?? 0);
            $metadata = [
                'name'          => $name,
                'id'            => $id,
                'password_name' => (string) ($password['name'] ?? ''),
                'uuid'          => (string) ($password['uuid'] ?? $index)
            ];

            if ($last_used > 0 && $last_used < $password_cutoff) {
                $metadata['last_used'] = wp_date('Y-m-d', $last_used);
                $issues[] = self::_format_issue(
                    'INACTIVE_APPLICATION_PASSWORD', $metadata, 'warning'
                );
            } elseif ($last_used === 0 && $created > 0 && $created < $password_cutoff) {
                $metadata['created'] = wp_date('Y-m-d', $created);
                $issues[] = self::_format_issue(
                    'UNUSED_APPLICATION_PASSWORD', $metadata, 'warning'
                );
            }

            if ($created > 0 && $created < $rotation_cutoff) {
                $metadata['created'] = wp_date('Y-m-d', $created);
                $issues[] = self::_format_issue(
                    'LONG_LIVED_APPLICATION_PASSWORD', $metadata, 'warning'
                );
            }
        }

        if ($tracker_started <= 0) {
            return;
        }

        if ($user->last_access !== null) {
            $last_access_at = strtotime((string) $user->last_access . ' UTC');

            if ($last_access_at !== false && $last_access_at > 0
                && $last_access_at < $account_cutoff
            ) {
                $issues[] = self::_format_issue('INACTIVE_USER_ACCOUNT', [
                    'name'        => $name,
                    'id'          => $id,
                    'last_access' => wp_date('Y-m-d', $last_access_at)
                ], 'warning');
            }
        } else {
            if ($last_password_use >= $account_cutoff) {
                return;
            }

            $registered_at = strtotime(get_gmt_from_date(
                (string) $user->user_registered
            ) . ' UTC');
            $observed_since = max(
                $tracker_started,
                $registered_at !== false ? $registered_at : 0
            );

            if ($observed_since < $account_cutoff) {
                $issues[] = self::_format_issue('NO_RECORDED_ACCOUNT_ACTIVITY', [
                    'name'           => $name,
                    'id'             => $id,
                    'observed_since' => wp_date('Y-m-d', $observed_since)
                ]);
            }
        }
    }

    /**
     * Get messages for account and credential findings
     *
     * @return array
     * @access private
     * @static
     *
     * @version 8.0.2
     */
    private static function _get_message_templates()
    {
        return [
            'INACTIVE_USER_ACCOUNT' => __(
                'User %s (ID %d) has not accessed the site since %s',
                'advanced-access-manager'
            ),
            'NO_RECORDED_ACCOUNT_ACTIVITY' => __(
                'User %s (ID %d) has no recorded activity since %s',
                'advanced-access-manager'
            ),
            'INACTIVE_APPLICATION_PASSWORD' => __(
                'Application password "%3$s" (%4$s) for user %1$s (ID %2$d) was last used on %5$s',
                'advanced-access-manager'
            ),
            'UNUSED_APPLICATION_PASSWORD' => __(
                'Application password "%3$s" (%4$s) for user %1$s (ID %2$d) has not been used since it was created on %5$s',
                'advanced-access-manager'
            ),
            'LONG_LIVED_APPLICATION_PASSWORD' => __(
                'Application password "%3$s" (%4$s) for user %1$s (ID %2$d) was created on %5$s and exceeds the rotation threshold',
                'advanced-access-manager'
            )
        ];
    }

}