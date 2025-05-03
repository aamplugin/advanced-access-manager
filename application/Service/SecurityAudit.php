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
     * Issue weights
     *
     * @version 7.0.0
     */
    const ISSUE_WEIGHT = [
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
     * @version 7.0.0
     */
    protected function __construct()
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

        // Keep the support RESTful service enabled at all times because it is used
        // by issue reporting feature as well
        AAM_Restful_SecurityAudit::bootstrap();
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
        return AAM::api()->db->delete(self::DB_OPTION)
            && AAM::api()->db->delete(self::DB_SCOPE_OPTION)
            && AAM::api()->db->delete(self::DB_SUMMARY_OPTION);
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
     * Execute security audit check
     *
     * @param string $check
     * @param bool   $reset
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function execute($check, $reset = false)
    {
        $checks = $this->get_steps();
        $report = [];

        if ($reset) {
            $this->reset();
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

            // Recalculate the score
            $score    = 100;
            $detected = [];

            foreach($report as $check => $results) {
                if (isset($results['issues'])) {
                    foreach($results['issues'] as $issue) {
                        $detected[$issue['code']] = $issue['type'];
                    }
                }
            }

            foreach($detected as $type) {
                $score -= self::ISSUE_WEIGHT[$type];
            }

            AAM::api()->db->write(
                self::DB_SCOPE_OPTION, $score > 0 ? $score : 0
            );
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
                'description' => __('Maintaining the integrity of WordPress core roles is essential. Altering or removing default roles can cause conflicts with plugins, lead to failed user registrations, and introduce integrity risks. This check ensures that the core roles remain intact, keeping your website stable and secure.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/preserving-wordpress-core-roles-avoid-conflicts'
            ],
            AAM_Audit_CoreUserRoleOptionIntegrityCheck::ID => [
                'title'       => __('Validate WordPress Roles & Capabilities Core Option Integrity', 'advanced-access-manager'),
                'step'        => AAM_Audit_CoreUserRoleOptionIntegrityCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_CoreUserRoleOptionIntegrityCheck::class,
                'description' => __('The core "_user_roles" option contains WordPress roles and capabilities. Altering its structure can break functionality, introduce vulnerabilities, and complicate updates. This check ensures that role modifications adhere to WordPress built-in APIs and do not compromise the underlying structure.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-user-role-security-and-integrity-warning'
            ],
            AAM_Audit_RoleCapabilityNamingConventionCheck::ID => array(
                'title'       => __('Verify Roles & Capabilities Naming Standards', 'advanced-access-manager'),
                'step'        => AAM_Audit_RoleCapabilityNamingConventionCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleCapabilityNamingConventionCheck::class,
                'description' => __('Adhering to WordPress naming conventions for roles and capabilities is vital for maintaining consistency, reducing errors, and ensuring compatibility across plugins and themes. This check promotes best practices in naming to help improve security and collaboration.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-role-capability-naming-conventions'
            ),
            AAM_Audit_RoleTransparencyCheck::ID => array(
                'title'       => __('Verify Roles Transparency', 'advanced-access-manager'),
                'step'        => AAM_Audit_RoleTransparencyCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleTransparencyCheck::class,
                'description' => __('Hidden roles can obscure access controls and create security concerns by making it difficult to audit permissions. This check flags hidden roles, helping you ensure full transparency and control over user access.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/hidden-wordpress-roles-website-access-management'
            ),
            AAM_Audit_EmptyUnusedRoleCheck::ID => array(
                'title'       => __('Identify Empty or Unused Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_EmptyUnusedRoleCheck::ID,
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_EmptyUnusedRoleCheck::class,
                'description' => __('Empty roles, which lack any assigned capabilities, or unused custom roles can pose some concerns if misused by plugins or themes. This check identifies such roles, enabling administrators to audit and remove them to avoid confusion.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/risks-registered-empty-roles-wordpress'
            ),
            AAM_Audit_HighPrivilegeRoleCheck::ID => array(
                'title'       => __('Detect High-Privilege Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeRoleCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeRoleCheck::class,
                'description' => __('Roles with high-level privileges carry significant risks. Users with access to core settings, file uploads, or theme/plugin management can introduce vulnerabilities or disrupt site functionality. This check flags high-privilege roles that may need review.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/misuse-high-privilege-capabilities-wordpress'
            ),
            AAM_Audit_HighPrivilegeOrElevatedUserCheck::ID => array(
                'title'       => __('Identify High-Privilege Users & Elevated Access', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeOrElevatedUserCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeOrElevatedUserCheck::class,
                'description' => __('Assigning high-privilege capabilities directly to users or expanding the number of users in high-privilege roles increases the potential attack surface. This check identifies users with elevated access to ensure that roles and capabilities are properly managed, minimizing security risks.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/security-risks-elevated-user-access-high-privilege-wordpress'
            ),
            AAM_Audit_HighPrivilegeContentModeratorCheck::ID => array(
                'title'       => __('Identify High-Privilege Content Moderator Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeContentModeratorCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeContentModeratorCheck::class,
                'description' => __('Assigning high-privilege content moderation capabilities in WordPress, poses significant security risks if granted to untrusted roles. These capabilities allow users to manipulate or delete live content, inject malware, and harm SEO performance, potentially leading to data loss and compromised site integrity. By carefully managing user roles and permissions, you can protect your website from potential cyber threats while ensuring content integrity.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-high-privilege-roles-content-moderation'
            ),
            AAM_Audit_HighPrivilegeUserCountCheck::ID => array(
                'title'       => __('Identify Elevated Number of High-Privilege Users', 'advanced-access-manager'),
                'step'        => AAM_Audit_HighPrivilegeUserCountCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeUserCountCheck::class,
                'description' => __('Having too many Administrator or high-privilege content moderation accounts on a WordPress site can seriously compromise security, as such account increases the risk of unauthorized access. Administrator accounts, with unrestricted control over the site, pose a significant threat if compromised, enabling attackers to install malware, alter site content, or hijack accounts. Even Editor accounts, though less powerful, allow users to modify and publish all posts, insert HTML and JavaScript, and upload files, which could lead to vulnerabilities like Cross-Site Scripting (XSS) or malware injection if an account is breached.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-too-many-admin-editor-accounts'
            ),
            AAM_Audit_ElevatedCoreRoleCheck::ID => array(
                'title'       => __('Flag Elevated Privileges for Core Roles', 'advanced-access-manager'),
                'step'        => AAM_Audit_ElevatedCoreRoleCheck::ID,
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_ElevatedCoreRoleCheck::class,
                'description' => __('Modifying core WordPress roles like Editor or Subscriber by granting extra capabilities can lead to security vulnerabilities. This check ensures that core roles remain as intended and recommends creating custom roles for extended functionality.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/dangers-modifying-default-wordpress-core-roles'
            ),
            AAM_Audit_RestfulAutoDiscoverEndpointCheck::ID => array(
                'title'       => __('Audit RESTful API Discovery Endpoint', 'advanced-access-manager'),
                'step'        => AAM_Audit_RestfulAutoDiscoverEndpointCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_RestfulAutoDiscoverEndpointCheck::class,
                'description' => __('The "/wp-json/" endpoint, part of WordPress RESTful API, can expose sensitive information to unauthorized users. This check audits the API endpoint to ensure that access is properly restricted to minimize the risk of exploitation.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/protect-wordpress-restful-api-auto-discover-endpoint'
            ),
            AAM_Audit_XmlRpcEndpointCheck::ID => array(
                'title'       => __('Audit XML-RPC Endpoint Access', 'advanced-access-manager'),
                'step'        => AAM_Audit_XmlRpcEndpointCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_XmlRpcEndpointCheck::class,
                'description' => __('The outdated XML-RPC endpoint can be a target for brute-force attacks and other exploits. This check assesses whether the endpoint is still enabled and recommends disabling it to strengthen site security.', 'advanced-access-manager'),
                'article'     => 'https://aamportal.com/article/disable-wordpress-xml-rpc-endpoint-security'
            ),
            AAM_Audit_EditableFileSystemCheck::ID => array(
                'title'       => __('Check Editable File System Permissions', 'advanced-access-manager'),
                'step'        => AAM_Audit_EditableFileSystemCheck::ID,
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_EditableFileSystemCheck::class,
                'description' => __('Writable WordPress file systems can lead to malware injection or backdoor installation. This check ensures that critical directories, like "/wp-content/plugins" and "/wp-content/themes", have read-only permissions to prevent unauthorized modifications.', 'advanced-access-manager'),
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

        return !empty($report);
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
        return AAM::api()->db->read(self::DB_SCOPE_OPTION);
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

        if (empty($score)) {
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