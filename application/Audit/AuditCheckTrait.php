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
     * Convert issue to message
     *
     * @param array $issue
     *
     * @return string|null
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function issue_to_message($issue)
    {
        $result = null;
        $map    = self::_get_message_templates();

        if ($issue['code'] === 'APPLICATION_ERROR') {
            $result = sprintf(
                __('Unexpected application error: %s', 'advanced-access-manager'),
                $issue['metadata']['message']
            );
        } elseif (array_key_exists($issue['code'], $map)) {
            if (!empty($issue['metadata'])) {
                $result = sprintf(
                    $map[$issue['code']],
                    ...array_values(array_map(function($v) {
                        return is_array($v) ? implode(', ', $v) : $v;
                    }, $issue['metadata']))
                );
            } else {
                $result = $map[$issue['code']];
            }
        }

        return $result;
    }

    /**
     * Convert audit step results into shareable dataset
     *
     * @param array $results
     *
     * @return array
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function issues_to_shareable($results)
    {
        return $results['issues'];
    }

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
     * @param string $code
     * @param array  $metadata [Optional]
     * @param string $type     [Optional]
     *
     * @return array
     *
     * @access private
     * @static
     *
     * @version 7.0.0
     */
    private static function _format_issue($code, $metadata = [], $type = 'notice')
    {
        $result = [
            'type' => $type,
            'code' => $code
        ];

        if (!empty($metadata)) {
            $result['metadata'] = $metadata;
        }

        return $result;
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