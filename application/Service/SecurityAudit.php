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
 * @version 6.9.40
 */
class AAM_Service_SecurityAudit
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.9.40
     */
    const FEATURE_FLAG = 'service.security_audit.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.40
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Security Scan', AAM_KEY),
                    'description' => __('This automated security scan service conducts a series of checks to verify the integrity of your website\'s configurations and detect any potential elevated privileges for users and roles.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 1);
        }

        // Keep the support RESTful service enabled at all times because it is used
        // by issue reporting feature as well
        AAM_Restful_SecurityAuditService::bootstrap();
    }

    /**
     * Determine if service is enabled
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.40
     */
    public function is_enabled()
    {
        return AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG)
            && current_user_can('aam_trigger_audit');
    }

    /**
     * Get security audit steps (checks)
     *
     * @return array
     *
     * @access public
     * @version 6.9.40
     */
    public function get_steps()
    {
        return apply_filters('aam_security_audit_checks_filter', [
            'core_roles_integrity' => [
                'title'       => __('Verify WordPress Core Roles Integrity', AAM_KEY),
                'step'        => 'core_roles_integrity',
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleIntegrityCheck::class,
                'description' => __('Maintaining the integrity of WordPress core roles is essential. Altering or removing default roles can cause conflicts with plugins, lead to failed user registrations, and introduce security risks. This check ensures that the core roles remain intact, keeping your website stable and secure.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/preserving-wordpress-core-roles-avoid-conflicts'
            ],
            'user_roles_option_integrity' => [
                'title'       => __('Validate WordPress Roles & Capabilities Core Option Integrity', AAM_KEY),
                'step'        => 'user_roles_option_integrity',
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_CoreUserRoleOptionIntegrityCheck::class,
                'description' => __('The core "_user_roles" option manages WordPress roles and capabilities. Altering its structure can break functionality, introduce vulnerabilities, and complicate updates. This check ensures that role modifications adhere to WordPress built-in APIs and do not compromise the underlying structure.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/wordpress-user-role-security-and-integrity-warning'
            ],
            'roles_caps_naming_convention' => array(
                'title'       => __('Enforce Roles & Capabilities Naming Standards', AAM_KEY),
                'step'        => 'roles_caps_naming_convention',
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleCapabilityNamingConventionCheck::class,
                'description' => __('Adhering to WordPress naming conventions for roles and capabilities is vital for maintaining consistency, reducing errors, and ensuring compatibility across plugins and themes. This check promotes best practices in naming to help improve security and collaboration.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/wordpress-role-capability-naming-conventions'
            ),
            'roles_visibility' => array(
                'title'       => __('Ensure Registered Roles Transparency', AAM_KEY),
                'step'        => 'roles_visibility',
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_RoleTransparencyCheck::class,
                'description' => __('Hidden roles can obscure access controls and create security risks by making it difficult to audit user permissions. This check flags hidden roles, helping you ensure full transparency and control over user access.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/hidden-wordpress-roles-website-access-management'
            ),
            'empty_roles_detection' => array(
                'title'       => __('Identify Empty or Unused Roles', AAM_KEY),
                'step'        => 'empty_roles_detection',
                'category'    => 'Roles & Capabilities',
                'executor'    => AAM_Audit_EmptyRoleCheck::class,
                'description' => __('Empty roles, which lack any assigned capabilities, can pose security risks if misused by plugins or themes. This check identifies such roles, enabling administrators to audit and remove them to avoid confusion and security vulnerabilities.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/risks-registered-empty-roles-wordpress'
            ),
            'high_privilege_roles' => array(
                'title'       => __('Detect High-Privilege Roles', AAM_KEY),
                'step'        => 'high_privilege_roles',
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeRoleCheck::class,
                'description' => __('Roles with high-level privileges carry significant risks. Users with access to core settings, file uploads, or theme/plugin management can introduce vulnerabilities or disrupt site functionality. This check flags high-privilege roles that may need review.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/misuse-high-privilege-capabilities-wordpress'
            ),
            'high_privilege_or_elevated_users' => array(
                'title'       => __('Identify High-Privilege Users & Elevated Access', AAM_KEY),
                'step'        => 'high_privilege_or_elevated_users',
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeOrElevatedUserCheck::class,
                'description' => __('Assigning high-privilege capabilities directly to users or expanding the number of users in high-privilege roles increases the potential attack surface. This check identifies users with elevated access to ensure that roles and capabilities are properly managed, minimizing security risks.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/security-risks-elevated-user-access-high-privilege-wordpress'
            ),
            'high_privilege_content_moderator_roles' => array(
                'title'       => __('Identify High-Privilege Content Moderator Roles', AAM_KEY),
                'step'        => 'high_privilege_content_moderator_roles',
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeContentModeratorCheck::class,
                'description' => __('Assigning high-privilege content moderation capabilities in WordPress, poses significant security risks if granted to untrusted roles. These capabilities allow users to manipulate or delete live content, inject malware, and harm SEO performance, potentially leading to data loss and compromised site integrity. By carefully managing user roles and permissions, you can protect your website from potential cyber threats while ensuring content integrity.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-high-privilege-roles-content-moderation'
            ),
            'high_privilege_users_count' => array(
                'title'       => __('Identified Elevated Number of High-Privilege Users', AAM_KEY),
                'step'        => 'high_privilege_users_count',
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_HighPrivilegeUserCountCheck::class,
                'description' => __('Having too many Administrator or high-privilege content moderation accounts on a WordPress site can seriously compromise security, as such account increases the risk of unauthorized access. Administrator accounts, with unrestricted control over the site, pose a significant threat if compromised, enabling attackers to install malware, alter site content, or hijack accounts. Even Editor accounts, though less powerful, allow users to modify and publish all posts, insert HTML and JavaScript, and upload files, which could lead to vulnerabilities like Cross-Site Scripting (XSS) or malware injection if an account is breached.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/wordpress-security-risks-too-many-admin-editor-accounts'
            ),
            'elevated_core_role_caps' => array(
                'title'       => __('Flag Elevated Privileges for Core Roles', AAM_KEY),
                'step'        => 'elevated_core_role_caps',
                'category'    => 'Access Strategy',
                'executor'    => AAM_Audit_ElevatedCoreRoleCheck::class,
                'description' => __('Modifying core WordPress roles like Editor or Subscriber by granting extra capabilities can lead to security vulnerabilities. This check ensures that core roles remain as intended and recommends creating custom roles for extended functionality.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/dangers-modifying-default-wordpress-core-roles'
            ),
            'restful_auto_discover_endpoint' => array(
                'title'       => __('Audit RESTful API Discovery Endpoint', AAM_KEY),
                'step'        => 'restful_auto_discover_endpoint',
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_RestfulAutoDiscoverEndpointCheck::class,
                'description' => __('The "/wp-json/" endpoint, part of WordPress RESTful API, can expose sensitive information to unauthorized users. This check audits the API endpoint to ensure that access is properly restricted to minimize the risk of exploitation.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/protect-wordpress-restful-api-auto-discover-endpoint'
            ),
            'xml_rpc_endpoint' => array(
                'title'       => __('Audit XML-RPC Endpoint Access', AAM_KEY),
                'step'        => 'xml_rpc_endpoint',
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_XmlRpcEndpointCheck::class,
                'description' => __('The outdated XML-RPC endpoint can be a target for brute-force attacks and other exploits. This check assesses whether the endpoint is still enabled and recommends disabling it to strengthen site security.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/disable-wordpress-xml-rpc-endpoint-security'
            ),
            'editable_file_system' => array(
                'title'       => __('Check Editable File System Permissions', AAM_KEY),
                'step'        => 'editable_file_system',
                'category'    => 'General Security Consideration',
                'executor'    => AAM_Audit_EditableFileSystemCheck::class,
                'description' => __('Writable WordPress file systems can lead to malware injection or backdoor installation. This check ensures that critical directories, like "/wp-content/plugins" and "/wp-content/themes", have read-only permissions to prevent unauthorized modifications.', AAM_KEY),
                'article'     => 'https://aamportal.com/article/risks-no-read-only-wordpress-file-system'
            )
        ]);
    }

    /**
     * Check if report exists
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.40
     */
    public function has_report()
    {
        $report = AAM_Core_API::getOption('aam_security_audit_result', null);

        return !empty($report);
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_SecurityAudit::bootstrap();
}