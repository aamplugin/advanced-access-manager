<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Security Audit service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_SecurityAudit
{
    use AAM_Service_BaseTrait;

    /**
     * Security audit result
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_security_audit_report';

    /**
     * Security audit last score
     *
     * @version 7.0.0
     */
    const DB_SCOPE_OPTION = 'aam_security_audit_score';

    /**
     * Executive summary for the audit report
     *
     * @version 7.0.0
     */
    const DB_SUMMARY_OPTION = 'aam_audit_executive_summary';

    /**
     * Human review decisions, stored separately from scan evidence.
     */
    const DB_REVIEW_OPTION = 'aam_security_audit_reviews';

    /**
     * Issue weights
     *
     * @version 7.0.0
     */
    const ISSUE_WEIGHT = [
        'error'    => 10,
        'critical' => 10,
        'warning'  => 5,
        'notice'   => 2
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        // Keep the support RESTful service enabled at all times because it is used
        // by issue reporting feature as well
        AAM_Restful_SecurityAudit::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize service hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        add_filter('aam_security_scan_enabled_filter', function() {
            return AAM::api()->config->get(AAM::SERVICES[__CLASS__], true);
        });

        // Register cron-job
        if (wp_next_scheduled('aam_security_audit_cron') === false) {
            wp_schedule_event(time(), 'daily', 'aam_security_audit_cron');
        }

        add_action('aam_security_audit_cron', function() {
            $this->_run_audit();
        });

        add_action('aam_uninstall_action', function() {
            wp_unschedule_event(
                wp_next_scheduled('aam_security_audit_cron'),
                'aam_security_audit_cron'
            );
        });
    }

    /**
     * Reset last audit results
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        $db = AAM::api()->db;
        $success = true;

        foreach ([self::DB_OPTION, self::DB_SCOPE_OPTION,
            self::DB_SUMMARY_OPTION, self::DB_REVIEW_OPTION] as $option) {
            if ($db->read($option) !== null && !$db->delete($option)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Read last audit report
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function read()
    {
        return AAM::api()->db->read(self::DB_OPTION, []);
    }

    /**
     * Add the same readable issue messages used by the legacy audit screen.
     * These display fields are not saved with the raw audit report.
     *
     * @param string $step
     * @param array  $result
     *
     * @return array
     */
    public function prepare_ui_result($step, $result)
    {
        $checks   = $this->get_steps();
        $executor = isset($checks[$step]['executor']) ? $checks[$step]['executor'] : null;
        $reviews  = AAM::api()->db->read(self::DB_REVIEW_OPTION, []);

        if (!is_array($result) || empty($result['issues'])) {
            return $result;
        }

        foreach ($result['issues'] as &$issue) {
            $issue['id'] = $this->get_issue_id($step, $issue);
            $issue['review'] = isset($reviews[$issue['id']])
                ? $reviews[$issue['id']] : null;

            if ($executor && is_callable([$executor, 'issue_to_message'])) {
                $message = call_user_func([$executor, 'issue_to_message'], $issue);
                if (is_string($message) && $message !== '') {
                    $issue['message'] = $message;
                }
            }

            // These checks include the user's name and ID in their message.
            if (in_array($step, [
                AAM_Audit_ApplicationPasswordCheck::ID,
                AAM_Audit_HighPrivilegeOrElevatedUserCheck::ID
            ], true) && !empty($issue['metadata']['id']) && !empty($issue['metadata']['name'])) {
                $user_id = absint($issue['metadata']['id']);
                if ($user_id && current_user_can('edit_user', $user_id)) {
                    $issue['user_profile'] = [
                        'name' => $issue['metadata']['name'],
                        'url'  => get_edit_user_link($user_id)
                    ];
                }
            }
        }
        unset($issue);

        return $result;
    }

    /**
     * Identify a finding across audits without storing its metadata in the key.
     */
    public function get_issue_id($step, $issue)
    {
        $identity = [
            'code'     => isset($issue['code']) ? $issue['code'] : '',
            'metadata' => isset($issue['metadata']) ? $issue['metadata'] : []
        ];

        return hash('sha256', $step . '|' . wp_json_encode(
            $this->_normalize_issue_identity($identity)
        ));
    }

    /**
     * Save a review decision for one finding or acknowledge a larger scope.
     * Acknowledgement keeps the score; resolution excludes the finding.
     * Neither action changes the raw scan evidence.
     */
    public function update_reviews($scope, $status, $step = '', $issue_id = '', $note = '')
    {
        if (!in_array($scope, ['issue', 'step', 'all'], true)
            || !in_array($status, ['open', 'acknowledged', 'resolved'], true)
            || ($scope !== 'issue' && $status !== 'acknowledged')) {
            throw new InvalidArgumentException('Invalid audit review action');
        }

        $report = $this->read();
        if (($scope === 'issue' || $scope === 'step') && !isset($report[$step])) {
            throw new OutOfRangeException('Audit step not found');
        }

        $targets = $scope === 'all' ? $report : [$step => $report[$step]];
        $reviews = AAM::api()->db->read(self::DB_REVIEW_OPTION, []);
        $updated = [];

        foreach ($targets as $step_key => $result) {
            foreach (isset($result['issues']) ? $result['issues'] : [] as $issue) {
                $id = $this->get_issue_id($step_key, $issue);
                if ($scope === 'issue' && $id !== $issue_id) {
                    continue;
                }
                if ($scope !== 'issue' && isset($reviews[$id])
                    && $reviews[$id]['status'] !== 'open') {
                    continue;
                }

                if ($status === 'open' && $note === '') {
                    unset($reviews[$id]);
                    $updated[$id] = null;
                } else {
                    $reviews[$id] = [
                        'status'     => $status,
                        'note'       => sanitize_textarea_field($note),
                        'updated_at' => time(),
                        'updated_by' => get_current_user_id()
                    ];
                    $updated[$id] = $reviews[$id];
                }
            }
        }

        if ($scope === 'issue' && !array_key_exists($issue_id, $updated)) {
            throw new OutOfRangeException('Audit finding not found');
        }

        if ($updated && !AAM::api()->db->write(self::DB_REVIEW_OPTION, $reviews, false)) {
            throw new RuntimeException('Audit review could not be saved');
        }

        $score = $this->_calculate_score($report, $reviews);
        AAM::api()->db->write(self::DB_SCOPE_OPTION, $score);

        return ['updated' => $updated, 'count' => count($updated), 'score' => $score];
    }

    /**
     * Use unresolved findings for the security score. Findings with the same
     * code retain the existing one-penalty-per-code scoring behavior.
     */
    private function _calculate_score($report, $reviews)
    {
        $detected = [];

        foreach ($report as $step => $results) {
            foreach (isset($results['issues']) ? $results['issues'] : [] as $issue) {
                $id = $this->get_issue_id($step, $issue);
                if (isset($reviews[$id]['status'])
                    && $reviews[$id]['status'] === 'resolved') {
                    continue;
                }

                $code = isset($issue['code']) ? $issue['code'] : $id;
                $type = isset($issue['type']) ? $issue['type'] : 'notice';
                $detected[$code] = $type;
            }
        }

        $score = 100;
        foreach ($detected as $type) {
            $score -= isset(self::ISSUE_WEIGHT[$type])
                ? self::ISSUE_WEIGHT[$type] : 0;
        }

        return max(0, $score);
    }

    /**
     * Canonicalize metadata so associative-key order cannot change a finding ID.
     */
    private function _normalize_issue_identity($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if ($value && array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }

        foreach ($value as &$item) {
            $item = $this->_normalize_issue_identity($item);
        }
        unset($item);

        return $value;
    }

    /**
     * Start a new scan while keeping acknowledgements for recurring findings.
     * A previously resolved finding must be reviewed again if it reappears.
     */
    private function _reset_for_scan()
    {
        $reviews = AAM::api()->db->read(self::DB_REVIEW_OPTION, []);
        foreach ($reviews as $id => $review) {
            if (isset($review['status']) && $review['status'] === 'resolved') {
                unset($reviews[$id]);
            }
        }

        AAM::api()->db->write(self::DB_REVIEW_OPTION, $reviews, false);
        AAM::api()->db->delete(self::DB_OPTION);
        AAM::api()->db->delete(self::DB_SCOPE_OPTION);
        AAM::api()->db->delete(self::DB_SUMMARY_OPTION);
    }

    /**
     * Execute security audit check
     *
     * @param string $check
     * @param bool   $reset
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function execute($check, $reset = false)
    {
        $checks = $this->get_steps();
        $report = [];

        if ($reset) {
            $this->_reset_for_scan();
        } else {
            $report = $this->read();
        }

        if (array_key_exists($check, $report)) {
            $current_result = $report[$check];
        } else {
            $current_result = [];
        }

        if (array_key_exists($check, $checks)) {
            $executor =  $checks[$check]['executor'];

            // Exclude already captures list of issues
            $result = call_user_func(
                $executor . '::run',
                array_filter($current_result, function($k) {
                    return $k !== 'issues';
                }, ARRAY_FILTER_USE_KEY)
            );

            // Merge the array of issues first
            $issues = [];

            if (isset($current_result['issues'])) {
                $issues = $current_result['issues'];
            }

            if (isset($result['issues'])) {
                $issues = array_merge($issues, $result['issues']);
            }

            // Storing results in db
            $report[$check]           = array_merge($current_result, $result);
            $report[$check]['issues'] = $issues;

            AAM::api()->db->write(self::DB_OPTION, $report, false);

            // Acknowledgements keep their weight; resolved findings do not.
            $score = $this->_calculate_score(
                $report,
                AAM::api()->db->read(self::DB_REVIEW_OPTION, [])
            );
            AAM::api()->db->write(self::DB_SCOPE_OPTION, $score);
        }

        return $report[$check];
    }

    /**
     * Get security audit steps (checks)
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_steps()
    {
        return apply_filters('aam_security_audit_checks_filter', [
            AAM_Audit_RoleIntegrityCheck::ID => [
                'title'       => __('Verify WordPress Core Roles Integrity', 'advanced-access-manager'),
                'step'        => AAM_Audit_RoleIntegrityCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleIntegrityCheck::class,
                'description' => __('Compares the built-in WordPress roles with their expected definitions. Missing or altered roles can change access unexpectedly and disrupt plugins that rely on them.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/preserving-wordpress-core-roles-avoid-conflicts'
            ],
            AAM_Audit_CoreUserRoleOptionIntegrityCheck::ID => [
                'title'       => __('Validate WordPress Roles & Capabilities Core Option Integrity', 'advanced-access-manager'),
                'step'        => AAM_Audit_CoreUserRoleOptionIntegrityCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_CoreUserRoleOptionIntegrityCheck::class,
                'description' => __('Checks the stored roles and capabilities option for invalid structure. If this data is damaged, WordPress may grant the wrong access or fail to recognize roles.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-user-role-security-and-integrity-warning'
            ],
            AAM_Audit_RoleCapabilityNamingConventionCheck::ID => array(
                'title'       => __('Verify Roles & Capabilities Naming Standards', 'advanced-access-manager'),
                'step'        => AAM_Audit_RoleCapabilityNamingConventionCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleCapabilityNamingConventionCheck::class,
                'description' => __('Flags role and capability names that do not follow WordPress conventions. Consistent names reduce collisions and make access rules easier to inspect.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-role-capability-naming-conventions'
            ),
            AAM_Audit_RoleTransparencyCheck::ID => array(
                'title'       => __('Verify Roles Transparency', 'advanced-access-manager'),
                'step'        => AAM_Audit_RoleTransparencyCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleTransparencyCheck::class,
                'description' => __('Looks for roles hidden from normal role lists. Hidden roles are harder to review and may conceal access that administrators do not expect.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/hidden-wordpress-roles-website-access-management'
            ),
            AAM_Audit_EmptyUnusedRoleCheck::ID => array(
                'title'       => __('Identify Empty or Unused Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_EmptyUnusedRoleCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_EmptyUnusedRoleCheck::class,
                'description' => __('Finds roles with no capabilities or no assigned users. Review them to understand whether they are intentional before removing them.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/risks-registered-empty-roles-wordpress'
            ),
            AAM_Audit_ApplicationPasswordCheck::ID => array(
                'title'       => __('Application Passwords Enabled', 'advanced-access-manager'),
                'step'        => AAM_Audit_ApplicationPasswordCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_ApplicationPasswordCheck::class,
                'description' => __('Lists accounts with application passwords, which can authenticate API requests without the account password. Review each integration and remove credentials no longer in use.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-application-passwords-invisible-credentials-to-monitor'
            ),
            AAM_Audit_HighPrivilegeRoleCheck::ID => array(
                'title'       => __('Detect High-Privilege Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeRoleCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeRoleCheck::class,
                'description' => __('Highlights roles with powerful capabilities, such as changing site settings or managing plugins. Confirm that each role needs this level of access.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/misuse-high-privilege-capabilities-wordpress'
            ),
            AAM_Audit_HighPrivilegeOrElevatedUserCheck::ID => array(
                'title'       => __('Identify High-Privilege Users & Elevated Access', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeOrElevatedUserCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeOrElevatedUserCheck::class,
                'description' => __('Finds users with powerful roles or directly assigned privileges. Check that these accounts still need elevated access and are actively managed.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/security-risks-elevated-user-access-high-privilege-wordpress'
            ),
            AAM_Audit_HighPrivilegeContentModeratorCheck::ID => array(
                'title'       => __('Identify High-Privilege Content Moderator Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeContentModeratorCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeContentModeratorCheck::class,
                'description' => __('Reviews roles that can edit, publish, or delete other people\'s content. A compromised account with these powers can change live pages and posts.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-high-privilege-roles-content-moderation'
            ),
            AAM_Audit_HighPrivilegeUserCountCheck::ID => array(
                'title'       => __('Identify Elevated Number of High-Privilege Users', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeUserCountCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeUserCountCheck::class,
                'description' => __('Counts accounts with administrator or broad content-management access. More privileged accounts mean more credentials to protect and review.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-too-many-admin-editor-accounts'
            ),
            AAM_Audit_ElevatedCoreRoleCheck::ID => array(
                'title'       => __('Flag Elevated Privileges for Core Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_ElevatedCoreRoleCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_ElevatedCoreRoleCheck::class,
                'description' => __('Detects extra privileges added to built-in roles such as Editor or Subscriber. Changes to a core role affect every account assigned to it.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/dangers-modifying-default-wordpress-core-roles'
            ),
            AAM_Audit_RestfulAutoDiscoverEndpointCheck::ID => array(
                'title'       => __('Audit RESTful API Discovery Endpoint', 'advanced-access-manager'),
                'step'        => AAM_Audit_RestfulAutoDiscoverEndpointCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_RestfulAutoDiscoverEndpointCheck::class,
                'description' => __('Checks whether the REST API discovery endpoint is publicly reachable. Its route list helps you review what the site exposes; public access may be expected for some sites.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/protect-wordpress-restful-api-auto-discover-endpoint'
            ),
            AAM_Audit_XmlRpcEndpointCheck::ID => array(
                'title'       => __('Audit XML-RPC Endpoint Access', 'advanced-access-manager'),
                'step'        => AAM_Audit_XmlRpcEndpointCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_XmlRpcEndpointCheck::class,
                'description' => __('Checks whether XML-RPC is enabled. If no integration needs it, disabling this login-capable endpoint reduces the paths attackers can probe.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/disable-wordpress-xml-rpc-endpoint-security'
            ),
            AAM_Audit_EditableFileSystemCheck::ID => array(
                'title'       => __('Check Editable File System Permissions', 'advanced-access-manager'),
                'step'        => AAM_Audit_EditableFileSystemCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_EditableFileSystemCheck::class,
                'description' => __('Checks whether sensitive WordPress files and directories are writable. Review unexpected write access while accounting for your update and deployment workflow.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/risks-no-read-only-wordpress-file-system'
            )
        ]);
    }

    /**
     * Check if report exists
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function has_report()
    {
        $report = AAM::api()->db->read(self::DB_SCOPE_OPTION);

        return $report !== null;
    }

    /**
     * Check if there is an executive summary
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function has_summary()
    {
        $summary = $this->get_summary();

        return !empty($summary);
    }

    /**
     * Get executive summary
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_summary()
    {
        return AAM::api()->db->read(self::DB_SUMMARY_OPTION);
    }

    /**
     * Read the latest score
     *
     * @return int|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_score()
    {
        $report = $this->read();
        if (!$report) {
            return AAM::api()->db->read(self::DB_SCOPE_OPTION);
        }

        $score = $this->_calculate_score(
            $report,
            AAM::api()->db->read(self::DB_REVIEW_OPTION, [])
        );
        if ($score !== AAM::api()->db->read(self::DB_SCOPE_OPTION)) {
            AAM::api()->db->write(self::DB_SCOPE_OPTION, $score);
        }

        return $score;
    }

    /**
     * Get score grade
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_score_grade()
    {
        $score  = $this->get_score();
        $result = __('Excellent', 'advanced-access-manager');

        if ($score === null) {
            $result = '';
        } elseif ($score < 75) {
            $result = __('Poor', 'advanced-access-manager');
        } elseif ($score <= 90) {
            $result = __('Moderate', 'advanced-access-manager');
        }

        return $result;
    }

    /**
     * This is a cron job that runs audit on a background
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _run_audit()
    {
        $first = true;
        $steps = array_keys($this->get_steps());

        do {
            $result = $this->execute($steps[0], $first === true);

            // No need to reset results anymore
            $first = false;

            if ($result['is_completed']) {
                array_shift($steps);
            }
        } while (!empty($steps));
    }

}
