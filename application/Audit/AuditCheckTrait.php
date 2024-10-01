<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Audit check trait with common methods
 *
 * @package AAM
 * @version 6.9.40
 */
trait AAM_Audit_AuditCheckTrait
{

    /**
     * Determine the final check status
     *
     * @param array $response
     *
     * @return string
     *
     * @access private
     * @static
     * @version 6.9.40
     */
    private static function _determine_check_status(&$result)
    {
        if ($result['is_completed']) {
            if (array_key_exists('issues', $result)) {
                $errors   = self::_filter_by_issue_type($result['issues'], 'error');
                $critical = self::_filter_by_issue_type($result['issues'], 'critical');
                $warnings = self::_filter_by_issue_type($result['issues'], 'warning');
                $notices  = self::_filter_by_issue_type($result['issues'], 'notice');

                if (!empty($errors)) {
                    $result['check_status'] = 'error';
                } elseif (!empty($critical)) {
                    $result['check_status'] = 'critical';
                } elseif (!empty($warnings)) {
                    $result['check_status'] = 'warning';
                } elseif (!empty($notices)) {
                    $result['check_status'] = 'notice';
                }
            } else {
                $result['check_status'] = 'ok';
            }
        }
    }

    /**
     * Filter failures by type
     *
     * @param array  $failures
     * @param string $issue_type
     *
     * @return array
     *
     * @access private
     * @static
     * @version 6.9.40
     */
    private static function _filter_by_issue_type($failures, $issue_type)
    {
        return array_filter($failures, function($f) use ($issue_type) {
            return $f['type'] === $issue_type;
        });
    }

    /**
     * Format detected issue
     *
     * @param string $reason
     * @param string $type
     *
     * @return array
     *
     * @access private
     * @static
     */
    private static function _format_issue($reason, $type = 'notice')
    {
        return [
            'type'   => $type,
            'reason' => $reason
        ];
    }

    /**
     * Read user_roles option from DB
     *
     * @return array
     *
     * @access private
     * @version 6.9.40
     */
    private static function _read_role_key_option()
    {
        global $wpdb;

        $role_key = $wpdb->get_blog_prefix(get_current_blog_id()) . 'user_roles';

        if (is_multisite()) {
            $result = get_blog_option(get_current_blog_id(), $role_key, []);
        } else {
            $result = get_option($role_key, []);
        }

        return $result;
    }

}