=== Advanced Access Manager - Restricted Content, Users & Roles, Enhanced Security and More ===
Contributors: vasyltech
Tags: security, access control, user roles, restricted content, api security
Requires at least: 5.8.0
Requires PHP: 5.6.0
Tested up to: 6.8.0
Stable tag: 7.0.1

Your WordPress security starts within — with AAM. Take control of your WordPress website and solve security gaps today.

== Description ==

Most security plugins protect against external threats like malware or brute force attacks, but what about internal risks? AAM secures your site from within by preventing unauthorized access, privilege escalation, and broken access controls — the leading security vulnerabilities in WordPress.

* **Mitigate Broken Access Controls.** Ensure roles and permissions are correctly configured to prevent unauthorized actions.
* **Eliminate Excessive Privileges**. Identify overpowered users and tighten access to critical site functions.
* **Harden Content Moderation**. Restrict who can edit, publish, or delete sensitive content.
* **Enforce Security with Code**. Define access rules as JSON Access Policies, making them portable, auditable, and automated.
* **Empower Developers with AAM PHP Framework**. Build custom, secure access controls with a robust set of services and APIs.

= Key Features =

* **Security Audit** – Instantly detect misconfigurations, compromised accounts, and risky role assignments.
* **Granular Access Control** – Manage permissions for any role, user, or visitor with fine-tuned restrictions.
* **Content Security** – Lock down posts, pages, media, terms and custom content types or taxonomies.
* **Role & Capability Management** – Customize WordPress roles and define capabilities with precision.
* **Backend & Menu Control** – Restrict dashboard areas and tailor admin menus per user or role.
* **API & Endpoint Management** – Secure RESTful and XML-RPC APIs by controlling who can access them.
* **Passwordless & Secure Logins** – Offer password-free login while keeping authentication safe.
* **Developer-Ready** – Utilize a one-of-a-kind [AAM PHP Framework](https://aamportal.com/reference/php-framework/) for custom security solutions.
* **Ad-Free & Transparent** – No ads, no bloat—just powerful security tools.

= Built for Security-Conscious WordPress Users =

AAM is trusted by over 150,000 websites to deliver enterprise-grade security without complexity. Whether you’re a site admin, developer, or security professional, AAM gives you the tools to take control of WordPress security — your way.

* Most features are free. Advanced capabilities are available through [premium add-ons](https://aamportal.com/premium).
* No hidden tracking, no data collection, no unwanted modifications — just security you can trust.

> Take control of your WordPress security today — install AAM now!

== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Manage access to backend menu
2. Manage access to metaboxes & widgets
3. Manage capabilities for roles and users
4. Manage access to posts, pages, media or custom post types
5. Posts and pages access options form
6. Define access to posts and categories while editing them
7. Manage access denied redirect rule
8. Manage user login redirect
9. Manage 404 redirect
10. Create your own content teaser for limited content
11. Improve your website security

== Changelog ==

= 7.0.1 =
* Fixed: Access Denied message when aam_access_dashboard capability is created [https://github.com/aamplugin/advanced-access-manager/issues/451](https://github.com/aamplugin/advanced-access-manager/issues/451)
* Fixed: PHP Warning: array_diff(): Expected parameter 1 to be an array, string given in /.../Service/Identity.php on line 245 [https://github.com/aamplugin/advanced-access-manager/issues/449](https://github.com/aamplugin/advanced-access-manager/issues/449)
* Fixed: Framework Manager error handling [https://github.com/aamplugin/advanced-access-manager/issues/448](https://github.com/aamplugin/advanced-access-manager/issues/448)
* Fixed: Error type E_PARSE in .../Framework/Utility/Misc.php on line 292. Error message: syntax error, unexpected ‘…’ [https://github.com/aamplugin/advanced-access-manager/issues/447](https://github.com/aamplugin/advanced-access-manager/issues/447)
* Fixed: PHP Fatal error. undefined function get_user [https://github.com/aamplugin/advanced-access-manager/issues/446](https://github.com/aamplugin/advanced-access-manager/issues/446)
* Fixed: PHP Fatal error. undefined function wp_is_rest_endpoint [https://github.com/aamplugin/advanced-access-manager/issues/445](https://github.com/aamplugin/advanced-access-manager/issues/445)
* Fixed: v2 api broken [https://github.com/aamplugin/advanced-access-manager/issues/444](https://github.com/aamplugin/advanced-access-manager/issues/444)
* Changed: Default to WordPress default logout redirect [https://github.com/aamplugin/advanced-access-manager/issues/450](https://github.com/aamplugin/advanced-access-manager/issues/450)

= 7.0.0 =
* Official 7.0.0

= 6.9.51 =
* Fixed: PHP Notice: Function _load_textdomain_just_in_time [https://github.com/aamplugin/advanced-access-manager/issues/442](https://github.com/aamplugin/advanced-access-manager/issues/442)
* Fixed: The Access Manager Metabox does not initialize correctly [https://github.com/aamplugin/advanced-access-manager/issues/441](https://github.com/aamplugin/advanced-access-manager/issues/441)
* Fixed: Incorrectly invoked translation function [https://github.com/aamplugin/advanced-access-manager/issues/440](https://github.com/aamplugin/advanced-access-manager/issues/440)
* Fixed: Download audit report issue [https://github.com/aamplugin/advanced-access-manager/issues/438](https://github.com/aamplugin/advanced-access-manager/issues/438)

= 6.9.49 =
* Fixed: Resetting all settings does not actually reset them all [https://github.com/aamplugin/advanced-access-manager/issues/436](https://github.com/aamplugin/advanced-access-manager/issues/436)
* New: Allow to prepare the executive audit report [https://github.com/aamplugin/advanced-access-manager/issues/437](https://github.com/aamplugin/advanced-access-manager/issues/437)

= 6.9.48 =
* Fixed: Notice in WordPress if the none-default language is active [https://github.com/aamplugin/advanced-access-manager/issues/435](https://github.com/aamplugin/advanced-access-manager/issues/435)
* Fixed: PHP Warning: Array to string conversion in /.../RoleTransparencyCheck.php on line 83 [https://github.com/aamplugin/advanced-access-manager/issues/433](https://github.com/aamplugin/advanced-access-manager/issues/433)
* New: Give the ability to share security audit report [https://github.com/aamplugin/advanced-access-manager/issues/434](https://github.com/aamplugin/advanced-access-manager/issues/434)

= 6.9.47 =
* Fixed: PHP Warning: Array to string conversion in /.../RoleTransparencyCheck.php on line 83 [https://github.com/aamplugin/advanced-access-manager/issues/433](https://github.com/aamplugin/advanced-access-manager/issues/433)

= 6.9.46 =
* Added: Run AAM Audit periodically [https://github.com/aamplugin/advanced-access-manager/issues/432](https://github.com/aamplugin/advanced-access-manager/issues/432)
* Added: Allow the ability to jump to a specific AAM tab [https://github.com/aamplugin/advanced-access-manager/issues/431](https://github.com/aamplugin/advanced-access-manager/issues/431)

= 6.9.45 =
* Added: Introduce AAM Security Score Widget [https://github.com/aamplugin/advanced-access-manager/issues/430](https://github.com/aamplugin/advanced-access-manager/issues/430)

= 6.9.44 =
* Removed: AI Chatbot service. We are moving it all to [aamportal.com](https://aamportal.com) website as Virtual assistant
* Removed: Contact form. We are changing our customer support policy and directing customers to the [contact us](https://aamportal.com/contact-us) page instead

= 6.9.43=
* Fixed: Can't update roles with whitespaces in slug [https://github.com/aamplugin/advanced-access-manager/issues/428](https://github.com/aamplugin/advanced-access-manager/issues/428)
* Added: Enhance Security Scan with additional steps [https://github.com/aamplugin/advanced-access-manager/issues/427](https://github.com/aamplugin/advanced-access-manager/issues/427)

= 6.9.42 =
* Fixed: UI bug fixes [https://github.com/aamplugin/advanced-access-manager/issues/426](https://github.com/aamplugin/advanced-access-manager/issues/426)
* Fixed: Custom user roles not copying correctly [https://github.com/aamplugin/advanced-access-manager/issues/419](https://github.com/aamplugin/advanced-access-manager/issues/419)
* Changed: DataTables warning and REST API Forbidden [https://github.com/aamplugin/advanced-access-manager/issues/420](https://github.com/aamplugin/advanced-access-manager/issues/420)

= 6.9.41 =
* Added: New Security Audit service [https://github.com/aamplugin/advanced-access-manager/issues/425](https://github.com/aamplugin/advanced-access-manager/issues/425)

= 6.9.39 =
* Fixed: Can't toggle capabilities when "Edit/Delete Capabilities" option is disabled [https://github.com/aamplugin/advanced-access-manager/issues/422](https://github.com/aamplugin/advanced-access-manager/issues/422)
* Fixed: Fail to create a custom capability with digits [https://github.com/aamplugin/advanced-access-manager/issues/423](https://github.com/aamplugin/advanced-access-manager/issues/423)
* Fixed: Deleted user may cause DataTables errors on AAM UI [https://github.com/aamplugin/advanced-access-manager/issues/424](https://github.com/aamplugin/advanced-access-manager/issues/424)

= 6.9.38 =
* Fixed: Capabilities that do not follow WP naming standards can't be toggled [https://github.com/aamplugin/advanced-access-manager/issues/418](https://github.com/aamplugin/advanced-access-manager/issues/418)
* Fixed: URL Access UI bug [https://github.com/aamplugin/advanced-access-manager/issues/417](https://github.com/aamplugin/advanced-access-manager/issues/417)
* Fixed: Incorrectly handled PostList AAM shortcode [https://github.com/aamplugin/advanced-access-manager/issues/416](https://github.com/aamplugin/advanced-access-manager/issues/416)
* Fixed: Uncaught TypeError: AAM_Framework_Service_Settings::set_settings(): Argument #1 [https://github.com/aamplugin/advanced-access-manager/issues/415](https://github.com/aamplugin/advanced-access-manager/issues/415)

= 6.9.37 =
* Fixed: Uncaught InvalidArgumentException: Redirect type allow does not accept status codes [https://github.com/aamplugin/advanced-access-manager/issues/413](https://github.com/aamplugin/advanced-access-manager/issues/413)
* Fixed: Incorrectly handled reduced permissions to AAM UI [https://github.com/aamplugin/advanced-access-manager/issues/414](https://github.com/aamplugin/advanced-access-manager/issues/414)
* Added: Allow to bypass recommended by WordPress core naming convention for capabilities [https://github.com/aamplugin/advanced-access-manager/issues/412](https://github.com/aamplugin/advanced-access-manager/issues/412)
* Added: Be more verbose with RESTful API errors [https://github.com/aamplugin/advanced-access-manager/issues/411](https://github.com/aamplugin/advanced-access-manager/issues/411)

= 6.9.36 =
* Fixed: [Allowed memory size of XXX bytes exhausted (tried to allocate YYY bytes)](https://github.com/aamplugin/advanced-access-manager/issues/407)
* Fixed: [Deprecated PHP notice](https://github.com/aamplugin/advanced-access-manager/issues/408)
* Fixed: [Not all admin menu items get properly protected](https://github.com/aamplugin/advanced-access-manager/issues/409)
* Added: [Allow the ability to report unexpected application errors](https://github.com/aamplugin/advanced-access-manager/issues/410)

= 6.9.35 =
* Fixed: PHP Fatal error: Uncaught Error: Call to undefined function switch_to_user_locale [https://github.com/aamplugin/advanced-access-manager/issues/398](https://github.com/aamplugin/advanced-access-manager/issues/398)
* Fixed: The Posts & Terms inheritance indicator is shown incorrectly [https://github.com/aamplugin/advanced-access-manager/issues/403](https://github.com/aamplugin/advanced-access-manager/issues/403)
* Fixed: Not all posts are listed on the Posts & Terms tab [https://github.com/aamplugin/advanced-access-manager/issues/399](https://github.com/aamplugin/advanced-access-manager/issues/399)
* Fixed: Role with only numeric numbers is not properly handled [https://github.com/aamplugin/advanced-access-manager/issues/400](https://github.com/aamplugin/advanced-access-manager/issues/400)
* Fixed: DataTables warning: table id=jwt-list - Requested unknown parameter '2' for row 0 [https://github.com/aamplugin/advanced-access-manager/issues/404](https://github.com/aamplugin/advanced-access-manager/issues/404)
* Fixed: Reset to default does not work properly in UI [https://github.com/aamplugin/advanced-access-manager/issues/401](https://github.com/aamplugin/advanced-access-manager/issues/401)
* Changed: By default, turn off the AI assistant [https://github.com/aamplugin/advanced-access-manager/issues/402](https://github.com/aamplugin/advanced-access-manager/issues/402)
* Added: Develop a shortcode that renders list of posts [https://github.com/aamplugin/advanced-access-manager/issues/405](https://github.com/aamplugin/advanced-access-manager/issues/405)

= 6.9.34 =
* Changed: Move AAM settings management to framework [https://github.com/aamplugin/advanced-access-manager/issues/396](https://github.com/aamplugin/advanced-access-manager/issues/396)
* Changed: Move AAM configuration management to framework [https://github.com/aamplugin/advanced-access-manager/issues/395](https://github.com/aamplugin/advanced-access-manager/issues/395)

= 6.9.33 =
* Fixed: AAM RESTful API does not honor user's selected language [https://github.com/aamplugin/advanced-access-manager/issues/394](https://github.com/aamplugin/advanced-access-manager/issues/394)
* Changed: Refactor how user status is handled [https://github.com/aamplugin/advanced-access-manager/issues/393](https://github.com/aamplugin/advanced-access-manager/issues/393)
* Changed: Revise RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/392](https://github.com/aamplugin/advanced-access-manager/issues/392)

= 6.9.32 =
* Fixed: The "Access Manager Metabox" does not function during initial load [https://github.com/aamplugin/advanced-access-manager/issues/391](https://github.com/aamplugin/advanced-access-manager/issues/391)
* Changed: Introduce RESTful API to manage users [https://github.com/aamplugin/advanced-access-manager/issues/390](https://github.com/aamplugin/advanced-access-manager/issues/390)
* Added: Extend Multisite support [https://github.com/aamplugin/advanced-access-manager/issues/389](https://github.com/aamplugin/advanced-access-manager/issues/389)

= 6.9.31 =
* Fixed: Overwritten flag for content resources does not take into consideration scope [https://github.com/aamplugin/advanced-access-manager/issues/385](https://github.com/aamplugin/advanced-access-manager/issues/385)
* Fixed: User expiration flag does not clear when resettings all AAM settings [https://github.com/aamplugin/advanced-access-manager/issues/382](https://github.com/aamplugin/advanced-access-manager/issues/382)
* Added: Fully develop "Content" RESTful API endpoints [https://github.com/aamplugin/advanced-access-manager/issues/386](https://github.com/aamplugin/advanced-access-manager/issues/386)
* Added: Give the ability to extend AAM Framework with additional methods [https://github.com/aamplugin/advanced-access-manager/issues/387](https://github.com/aamplugin/advanced-access-manager/issues/387)
* Changed: Move "Posts & Terms" reset feature to RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/384](https://github.com/aamplugin/advanced-access-manager/issues/384)
* Changed: Speed-up AAM PHP Framework [https://github.com/aamplugin/advanced-access-manager/issues/388](https://github.com/aamplugin/advanced-access-manager/issues/388)

= 6.9.30 =
* Fixed: The list of terms for any selected taxonomy is not listed [https://github.com/aamplugin/advanced-access-manager/issues/376](https://github.com/aamplugin/advanced-access-manager/issues/376)
* Fixed: An error of type E_PARSE was caused in line 210 of the file /.../application/Backend/Manager.php [https://github.com/aamplugin/advanced-access-manager/issues/377](https://github.com/aamplugin/advanced-access-manager/issues/377)
* Fixed: Incorrectly merging access controls for identity governance service with multirole support [https://github.com/aamplugin/advanced-access-manager/issues/378](https://github.com/aamplugin/advanced-access-manager/issues/378)
* Fixed: Internal inheritance info incorrectly set for Default access level [https://github.com/aamplugin/advanced-access-manager/issues/379](https://github.com/aamplugin/advanced-access-manager/issues/379)

= 6.9.29 =
* Fixed: Warning: Attempt to read property "capabilities" on null in [https://github.com/aamplugin/advanced-access-manager/issues/374](https://github.com/aamplugin/advanced-access-manager/issues/374)
* Fixed: Warning: Attempt to read property "ID" on bool in [https://github.com/aamplugin/advanced-access-manager/issues/373](https://github.com/aamplugin/advanced-access-manager/issues/373)
* Fixed: Stripped query strings in new account "set password" links AND Disallowed password reset [https://github.com/aamplugin/advanced-access-manager/issues/372](https://github.com/aamplugin/advanced-access-manager/issues/372)
* Changed: Move Posts & Terms service to RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/375](https://github.com/aamplugin/advanced-access-manager/issues/375)

= 6.9.28 =
* Fixed: Message: base64_encode(): Passing null to parameter #1 [https://github.com/aamplugin/advanced-access-manager/issues/370](https://github.com/aamplugin/advanced-access-manager/issues/370)
* Fixed: Menu items that recycle the same ID are incorrectly handled [https://github.com/aamplugin/advanced-access-manager/issues/364](https://github.com/aamplugin/advanced-access-manager/issues/364)
* Fixed: The login brute-force feature incorrectly counts hits [https://github.com/aamplugin/advanced-access-manager/issues/366](https://github.com/aamplugin/advanced-access-manager/issues/366)
* Changed: Take into account multi-byte URLs [https://github.com/aamplugin/advanced-access-manager/issues/371](https://github.com/aamplugin/advanced-access-manager/issues/371)
* Added: New "User Governance" feature [https://github.com/aamplugin/advanced-access-manager/issues/369](https://github.com/aamplugin/advanced-access-manager/issues/369)
* Added: Allow the ability to chain method return value for Access Policy marker path [https://github.com/aamplugin/advanced-access-manager/issues/365](https://github.com/aamplugin/advanced-access-manager/issues/365)
* Added: Add "in" and "not in" operand to the Hook filters [https://github.com/aamplugin/advanced-access-manager/issues/367](https://github.com/aamplugin/advanced-access-manager/issues/367)
* Added: Allow the ability to turn off "Aarmie AI Chatbot" [https://github.com/aamplugin/advanced-access-manager/issues/368](https://github.com/aamplugin/advanced-access-manager/issues/368)
* Deprecated: The "User Level Filters" [https://aamportal.com/article/demystifying-the-aam-user-level-filter-service](https://aamportal.com/article/demystifying-the-aam-user-level-filter-service)

= 6.9.27 =
* Fixed: Backend Menu & Toolbar services do not show any items [https://github.com/aamplugin/advanced-access-manager/issues/362](https://github.com/aamplugin/advanced-access-manager/issues/362)
* Added: Introduce Aarmie virtual assistant [https://github.com/aamplugin/advanced-access-manager/issues/361](https://github.com/aamplugin/advanced-access-manager/issues/361)

= 6.9.26 =
* Fixed: Metaboxes & Widgets service appends list for each refresh [https://github.com/aamplugin/advanced-access-manager/issues/358](https://github.com/aamplugin/advanced-access-manager/issues/358)
* Fixed: Fatal error: No such file or directory in /.../application/Core/Migration.php:184 [https://github.com/aamplugin/advanced-access-manager/issues/357](https://github.com/aamplugin/advanced-access-manager/issues/357)
* New:   Add the ability to change HTTP status code for "Access Denied" message [https://github.com/aamplugin/advanced-access-manager/issues/359](https://github.com/aamplugin/advanced-access-manager/issues/359)
* Changed: Revise all redirect functionality and standardize internal implementation [https://github.com/aamplugin/advanced-access-manager/issues/360](https://github.com/aamplugin/advanced-access-manager/issues/360)

= 6.9.25 =
* Fixed: Access Policy Param "Enforce" did not enforce [https://github.com/aamplugin/advanced-access-manager/issues/355](https://github.com/aamplugin/advanced-access-manager/issues/355)
* Fixed: Uncaught TypeError: AAM_Core_Policy_Token::evaluate(): Argument #3 ($args) must be of type array, null given [https://github.com/aamplugin/advanced-access-manager/issues/353](https://github.com/aamplugin/advanced-access-manager/issues/353)
* Added: Enhance Hook Access Policy Resource [https://github.com/aamplugin/advanced-access-manager/issues/354](https://github.com/aamplugin/advanced-access-manager/issues/354)

= 6.9.24 =
* Fixed: Incorrectly merged access controls with 3 or more roles [https://github.com/aamplugin/advanced-access-manager/issues/352](https://github.com/aamplugin/advanced-access-manager/issues/352)
* Fixed: Unnecessary forward slashes escapes in Access Policies [https://github.com/aamplugin/advanced-access-manager/issues/350](https://github.com/aamplugin/advanced-access-manager/issues/350)
* New: Added the "Operator" option for Access Policies Conditions [https://github.com/aamplugin/advanced-access-manager/issues/351](https://github.com/aamplugin/advanced-access-manager/issues/351)
* New: Added support for IP CIDR annotations for Access Policies [https://github.com/aamplugin/advanced-access-manager/issues/349](https://github.com/aamplugin/advanced-access-manager/issues/349)

= 6.9.23 =
* Fixed: Type E_Error in Visibility.php [https://github.com/aamplugin/advanced-access-manager/issues/347](https://github.com/aamplugin/advanced-access-manager/issues/347)
* Fixed: The previous selected role does not visually uncheck if switched to manage visitors or default [https://github.com/aamplugin/advanced-access-manager/issues/348](https://github.com/aamplugin/advanced-access-manager/issues/348)
* Changed: The minimum required WordPress version was lifted from 5.0.0 to 5.2.0.

= 6.9.22 =
* Fixed: Redirect to login page for visitors does not work [https://github.com/aamplugin/advanced-access-manager/issues/346](https://github.com/aamplugin/advanced-access-manager/issues/346)
* Fixed: Fatal error: Uncaught TypeError: method_exists() [https://github.com/aamplugin/advanced-access-manager/issues/344](https://github.com/aamplugin/advanced-access-manager/issues/344)
* Changed: Added "mergeAlign.limit" property [https://github.com/aamplugin/advanced-access-manager/issues/345](https://github.com/aamplugin/advanced-access-manager/issues/345)
* Changed: Change how ${USER.ip} marker works [https://github.com/aamplugin/advanced-access-manager/issues/338](https://github.com/aamplugin/advanced-access-manager/issues/338)

= 6.9.21 =
* Fixed: Content visibility issue with multi-role setup [https://github.com/aamplugin/advanced-access-manager/issues/342](https://github.com/aamplugin/advanced-access-manager/issues/342)
* Fixed: URL Access feature does not save "Redirect to page" [https://github.com/aamplugin/advanced-access-manager/issues/339](https://github.com/aamplugin/advanced-access-manager/issues/339)
* Changed: Enhance plugins security pasture [https://github.com/aamplugin/advanced-access-manager/issues/341](https://github.com/aamplugin/advanced-access-manager/issues/341)

= 6.9.20 =
* Fixed: When deleting URL Access rule, the "Unexpected Application Error" is displayed [https://github.com/aamplugin/advanced-access-manager/issues/337](https://github.com/aamplugin/advanced-access-manager/issues/337)
* Fixed: URL Access does not correctly handle multiple roles [https://github.com/aamplugin/advanced-access-manager/issues/336](https://github.com/aamplugin/advanced-access-manager/issues/336)
* Changed: Add-ons page overhaul [https://github.com/aamplugin/advanced-access-manager/issues/335](https://github.com/aamplugin/advanced-access-manager/issues/335)

= 6.9.19 =
* Fixed: Handling "Profile" submenu access [https://github.com/aamplugin/advanced-access-manager/issues/334](https://github.com/aamplugin/advanced-access-manager/issues/334)
* Fixed: Passing null to parameter #2 ($string) of type string is deprecated in /../Content.php on line 223 [https://github.com/aamplugin/advanced-access-manager/issues/333](https://github.com/aamplugin/advanced-access-manager/issues/333)
* Fixed: Undefined array key 2 in /../application/Core/Object/Menu.php on line 136 [](https://github.com/aamplugin/advanced-access-manager/issues/331https://github.com/aamplugin/advanced-access-manager/issues/331)
* Changed: Improve Login Redirect Shortcode redirect [https://github.com/aamplugin/advanced-access-manager/issues/332](https://github.com/aamplugin/advanced-access-manager/issues/332)

= 6.9.18 =
* Fixed: DataTables alert when URL Access service has at least one rule [https://github.com/aamplugin/advanced-access-manager/issues/330](https://github.com/aamplugin/advanced-access-manager/issues/330)
* Fixed: AAM core caching override [https://github.com/aamplugin/advanced-access-manager/issues/329](https://github.com/aamplugin/advanced-access-manager/issues/329)
* Fixed: PHP Deprecated: preg_replace(): Passing null to parameter [https://github.com/aamplugin/advanced-access-manager/issues/326](https://github.com/aamplugin/advanced-access-manager/issues/326)
* Changed: Update core API to allow defining option autoload [https://github.com/aamplugin/advanced-access-manager/issues/328](https://github.com/aamplugin/advanced-access-manager/issues/328)
* Changed: Update the "Welcome" service to include most common use-cases [https://github.com/aamplugin/advanced-access-manager/issues/327](https://github.com/aamplugin/advanced-access-manager/issues/327)

= 6.9.17 =
* Fixed: Fatal error: array_merge(): Argument #2 must be of type array, string given in .../LoginForm.php:46 [https://github.com/aamplugin/advanced-access-manager/issues/318](https://github.com/aamplugin/advanced-access-manager/issues/318)
* Fixed: Custom HTML message is escaped [https://github.com/aamplugin/advanced-access-manager/issues/322](https://github.com/aamplugin/advanced-access-manager/issues/322)
* Added New: Add the ability to add additional properties to URL Access form [https://github.com/aamplugin/advanced-access-manager/issues/320](https://github.com/aamplugin/advanced-access-manager/issues/320)
* Added New: Enhance Access Policy Hook resource [https://github.com/aamplugin/advanced-access-manager/issues/323](https://github.com/aamplugin/advanced-access-manager/issues/323)
* Changed: Move away from WP core transients [https://github.com/aamplugin/advanced-access-manager/issues/319](https://github.com/aamplugin/advanced-access-manager/issues/319)
* Changed: Move xpath resolver to its own class [https://github.com/aamplugin/advanced-access-manager/issues/321](https://github.com/aamplugin/advanced-access-manager/issues/321)
* Changed: Change the RESTful API rest_pre_dispatch filter priority [https://github.com/aamplugin/advanced-access-manager/issues/324](https://github.com/aamplugin/advanced-access-manager/issues/324)
* Changed: Changed the minimum required WP version to 5.0.0 [https://github.com/aamplugin/advanced-access-manager/issues/325](https://github.com/aamplugin/advanced-access-manager/issues/325)

= 6.9.16 =
* Fixed: Error when trying to edit the menu [https://github.com/aamplugin/advanced-access-manager/issues/315](https://github.com/aamplugin/advanced-access-manager/issues/315)
* Breaking Change: Removed `callback` attribute from `aam` shortcodes [https://github.com/aamplugin/advanced-access-manager/issues/316](https://github.com/aamplugin/advanced-access-manager/issues/316)
* Changed: Improved shortcode remote IP detection [https://github.com/aamplugin/advanced-access-manager/issues/317](https://github.com/aamplugin/advanced-access-manager/issues/317)

= 6.9.14 =
* Fixed: PHP deprecated notices [https://github.com/aamplugin/advanced-access-manager/issues/305](https://github.com/aamplugin/advanced-access-manager/issues/305)
* Fixed: Admin Menu get corrupted if the first submenu is restricted [https://github.com/aamplugin/advanced-access-manager/issues/307](https://github.com/aamplugin/advanced-access-manager/issues/307)
* Fixed: Multipage role list malfunction [https://github.com/aamplugin/advanced-access-manager/issues/306](https://github.com/aamplugin/advanced-access-manager/issues/306)
* Fixed: Empty error message when role fail to create [https://github.com/aamplugin/advanced-access-manager/issues/310](https://github.com/aamplugin/advanced-access-manager/issues/310)
* Changed: Adding ref=plugin query param to all links that point to aamportal.com [https://github.com/aamplugin/advanced-access-manager/issues/308](https://github.com/aamplugin/advanced-access-manager/issues/308)
* Added New: Introduce Access Denied Redirect RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/309](https://github.com/aamplugin/advanced-access-manager/issues/309)

= 6.9.13 =
* Fixed: User object does not initialize after login [https://github.com/aamplugin/advanced-access-manager/issues/300](https://github.com/aamplugin/advanced-access-manager/issues/300)
* Fixed: Wildcard for URL Access malfunction [https://github.com/aamplugin/advanced-access-manager/issues/296](https://github.com/aamplugin/advanced-access-manager/issues/296)
* Fixed: Restoring a previous Policy Revision adds backslashes (thank you @solaceten) [https://github.com/aamplugin/advanced-access-manager/issues/294](https://github.com/aamplugin/advanced-access-manager/issues/294)
* Fixed: Incorrectly handled login redirect with access policy [https://github.com/aamplugin/advanced-access-manager/issues/299](https://github.com/aamplugin/advanced-access-manager/issues/299)
* Changed: Move toolbar cache to transient & increase cache ttl [https://github.com/aamplugin/advanced-access-manager/issues/297](https://github.com/aamplugin/advanced-access-manager/issues/297)
* Added New: Add additional helpful tips to the AAM UI [https://github.com/aamplugin/advanced-access-manager/issues/298](https://github.com/aamplugin/advanced-access-manager/issues/298)
* Added New: Introduce Metaboxes & Widgets RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/301](https://github.com/aamplugin/advanced-access-manager/issues/301)
* Added New: Introduce Backend Menu RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/293](https://github.com/aamplugin/advanced-access-manager/issues/293)
* Added New: Introduce Admin Toolbar RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/302](https://github.com/aamplugin/advanced-access-manager/issues/302)
* Added New: Add notification about premium add-on update availability [https://github.com/aamplugin/advanced-access-manager/issues/303](https://github.com/aamplugin/advanced-access-manager/issues/303)
* Added New: Introduce restricted mode for RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/304](https://github.com/aamplugin/advanced-access-manager/issues/304)

= 6.9.12 =
* Fixed: URL Access skips query params for new rules [https://github.com/aamplugin/advanced-access-manager/issues/283](https://github.com/aamplugin/advanced-access-manager/issues/283)
* Fixed: Access policy does not apply for newly logged in user [https://github.com/aamplugin/advanced-access-manager/issues/286](https://github.com/aamplugin/advanced-access-manager/issues/286)
* Fixed: Compatibility with PHP 5.6 [https://github.com/aamplugin/advanced-access-manager/issues/287](https://github.com/aamplugin/advanced-access-manager/issues/287)
* Changed: Rewrite the Login Redirect service to use RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/285](https://github.com/aamplugin/advanced-access-manager/issues/285)
* Changed: Rewrite the Logout Redirect service to use RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/291](https://github.com/aamplugin/advanced-access-manager/issues/291)
* Changed: Rewrite the 404 Redirect service to use RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/292](https://github.com/aamplugin/advanced-access-manager/issues/292)
* Changed: Backend Menu UI improvement [https://github.com/aamplugin/advanced-access-manager/issues/288](https://github.com/aamplugin/advanced-access-manager/issues/288)
* Changed: Admin toolbar UI improvement [https://github.com/aamplugin/advanced-access-manager/issues/289](https://github.com/aamplugin/advanced-access-manager/issues/289)
* Changed: Metaboxes & Widgets UI improvement [https://github.com/aamplugin/advanced-access-manager/issues/290](https://github.com/aamplugin/advanced-access-manager/issues/290)
* Added New: Allow redefining the login message when access is restricted [https://github.com/aamplugin/advanced-access-manager/issues/284](https://github.com/aamplugin/advanced-access-manager/issues/284)

= 6.9.11 =
* Fixed: Change role does not work for expired access [https://github.com/aamplugin/advanced-access-manager/issues/279](https://github.com/aamplugin/advanced-access-manager/issues/279)
* Changed: Enhance JWT Token RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/278](https://github.com/aamplugin/advanced-access-manager/issues/278)
* Changed: Replace deprecated DateTime format [https://github.com/aamplugin/advanced-access-manager/issues/281](https://github.com/aamplugin/advanced-access-manager/issues/281)
* Changed: Improve user experience during plugin activation [https://github.com/aamplugin/advanced-access-manager/issues/282](https://github.com/aamplugin/advanced-access-manager/issues/282)

= 6.9.10 =
* Fixed: Can't see AAM settings when editing page [https://github.com/aamplugin/advanced-access-manager/issues/270](https://github.com/aamplugin/advanced-access-manager/issues/270)
* Fixed: The set_slug should not sanitize key [https://github.com/aamplugin/advanced-access-manager/issues/271](https://github.com/aamplugin/advanced-access-manager/issues/271)
* Fixed: Admin Menu restriction edge-case [https://github.com/aamplugin/advanced-access-manager/issues/272](https://github.com/aamplugin/advanced-access-manager/issues/272)
* Changed: Refactor JWT Token Service [https://github.com/aamplugin/advanced-access-manager/issues/273](https://github.com/aamplugin/advanced-access-manager/issues/273)
* Changed: Refactor the API Route Service [https://github.com/aamplugin/advanced-access-manager/issues/274](https://github.com/aamplugin/advanced-access-manager/issues/274)
* Changed: Normalize Role ID [https://github.com/aamplugin/advanced-access-manager/issues/275](https://github.com/aamplugin/advanced-access-manager/issues/275)
* Changed: Stop using user_status column [https://github.com/aamplugin/advanced-access-manager/issues/276](https://github.com/aamplugin/advanced-access-manager/issues/276)

= 6.9.9 =
* Fixed: Undefined array key "callback" [https://github.com/aamplugin/advanced-access-manager/issues/264](https://github.com/aamplugin/advanced-access-manager/issues/264)
* Fixed: PHP Deprecated: strpos(): Passing null to parameter #1 ($haystack) of type string [https://github.com/aamplugin/advanced-access-manager/issues/265](https://github.com/aamplugin/advanced-access-manager/issues/265)
* Changed: Disabling the "Render Access Manager Metabox" by default [https://github.com/aamplugin/advanced-access-manager/issues/268](https://github.com/aamplugin/advanced-access-manager/issues/268)
* Changed: https://github.com/aamplugin/advanced-access-manager/issues/266 [https://github.com/aamplugin/advanced-access-manager/issues/266](https://github.com/aamplugin/advanced-access-manager/issues/266)
* Added: Include MU plugins in the policy dependency check [https://github.com/aamplugin/advanced-access-manager/issues/267](https://github.com/aamplugin/advanced-access-manager/issues/267)

= 6.9.8 =
* Fixed: Fix the missing token_expires [https://github.com/aamplugin/advanced-access-manager/issues/263](https://github.com/aamplugin/advanced-access-manager/issues/263)
* Fixed: DataTables warning: table id=role-list - Ajax error [https://github.com/aamplugin/advanced-access-manager/issues/262](https://github.com/aamplugin/advanced-access-manager/issues/262)
* Fixed: List of users does not filter [https://github.com/aamplugin/advanced-access-manager/issues/261](https://github.com/aamplugin/advanced-access-manager/issues/261)

= 6.9.7 =
* Fixed: DataTables warning: table id=role-list [https://github.com/aamplugin/advanced-access-manager/issues/258](https://github.com/aamplugin/advanced-access-manager/issues/258)
* Fixed: PHP Fatal error: Uncaught ArgumentCountError [https://github.com/aamplugin/advanced-access-manager/issues/259](https://github.com/aamplugin/advanced-access-manager/issues/259)
* Added New: Warn user about disabling RESTful API [https://github.com/aamplugin/advanced-access-manager/issues/260](https://github.com/aamplugin/advanced-access-manager/issues/260)

= 6.9.6 =
* Fixed: Role XXX already exists [https://github.com/aamplugin/advanced-access-manager/issues/250](https://github.com/aamplugin/advanced-access-manager/issues/250)
* Fixed: Clean-up deprecated warnings [https://github.com/aamplugin/advanced-access-manager/issues/252](https://github.com/aamplugin/advanced-access-manager/issues/252)
* Added New: RESTful API to manage Roles [https://github.com/aamplugin/advanced-access-manager/issues/253](https://github.com/aamplugin/advanced-access-manager/issues/253)
* Added New: Introducing AAM Developer Framework [https://github.com/aamplugin/advanced-access-manager/issues/254](https://github.com/aamplugin/advanced-access-manager/issues/254)
* Added New: Enhance AAM API to allow settings reset [https://github.com/aamplugin/advanced-access-manager/issues/249](https://github.com/aamplugin/advanced-access-manager/issues/249)
* Changed: Simplify premium offering functionality further [https://github.com/aamplugin/advanced-access-manager/issues/255](https://github.com/aamplugin/advanced-access-manager/issues/255)
* Changed: Remove all references to aamplugin.com [https://github.com/aamplugin/advanced-access-manager/issues/256](https://github.com/aamplugin/advanced-access-manager/issues/256)

= 6.9.5 =
* Fixed: Duplicated ConfigPress editor [https://github.com/aamplugin/advanced-access-manager/issues/241](https://github.com/aamplugin/advanced-access-manager/issues/241)
* Changed: Switch to aamportal.com API for the premium add-ons [https://github.com/aamplugin/advanced-access-manager/issues/243](https://github.com/aamplugin/advanced-access-manager/issues/243)
* Changed: Improve AAM Admin Menu feature performance [https://github.com/aamplugin/advanced-access-manager/issues/240](https://github.com/aamplugin/advanced-access-manager/issues/240)

= 6.9.4 =
* Fixed: Incorrectly escaped string values [https://github.com/aamplugin/advanced-access-manager/issues/239](https://github.com/aamplugin/advanced-access-manager/issues/239)
* Fixed: Incorrectly handled revoked token validation [https://github.com/aamplugin/advanced-access-manager/issues/238](https://github.com/aamplugin/advanced-access-manager/issues/238)
* Fixed: Super-Admin is unable to re-assign roles in network sites [https://github.com/aamplugin/advanced-access-manager/issues/180](https://github.com/aamplugin/advanced-access-manager/issues/180)

= 6.9.3 =
* Fixed: Fatal error: Uncaught TypeError: count(): Argument #1 ($value) must be of type Countable... [https://github.com/aamplugin/advanced-access-manager/issues/236](https://github.com/aamplugin/advanced-access-manager/issues/236)
* Fixed: Warning: Undefined variable $value in... [https://github.com/aamplugin/advanced-access-manager/issues/235](https://github.com/aamplugin/advanced-access-manager/issues/235)
* Changed: Deprecating offering of some AAM premium add-ons [https://github.com/aamplugin/advanced-access-manager/issues/237](https://github.com/aamplugin/advanced-access-manager/issues/237)

= 6.9.2 =
* Fixed: Compliance with WordPress.org code quality [https://github.com/aamplugin/advanced-access-manager/issues/229](https://github.com/aamplugin/advanced-access-manager/issues/229)

= 6.9.1 =
* Fixed: Incorrectly stripped backslashes for Access Policy [https://github.com/aamplugin/advanced-access-manager/issues/228](https://github.com/aamplugin/advanced-access-manager/issues/228)
* Fixed: PHP Notice: Function AAM_Backend_Subject::hasCapability was called incorrectly [https://github.com/aamplugin/advanced-access-manager/issues/227](https://github.com/aamplugin/advanced-access-manager/issues/227)
* Fixed: PHP Notice: Undefined offset: -1 in [https://github.com/aamplugin/advanced-access-manager/issues/226](https://github.com/aamplugin/advanced-access-manager/issues/226)
* Added New: Add the ability to hook into filter [https://github.com/aamplugin/advanced-access-manager/issues/225](https://github.com/aamplugin/advanced-access-manager/issues/225)

= 6.9.0 =
* Fixed: Revoking JWT token via UI causes current user to logout [https://github.com/aamplugin/advanced-access-manager/issues/224](https://github.com/aamplugin/advanced-access-manager/issues/224)
* Fixed: Notice: Undefined variable: cache [https://github.com/aamplugin/advanced-access-manager/issues/223](https://github.com/aamplugin/advanced-access-manager/issues/223)
* Changed: Update JWT vendor [https://github.com/aamplugin/advanced-access-manager/issues/221](https://github.com/aamplugin/advanced-access-manager/issues/221)

= 6.0.0 =
* Complete rewrite of the entire plugin. For more information, check [this article](https://aamplugin.com/article/advanced-access-manager-next-generation)

= 5.0 =
* Added ACCESS COUNTER option to Posts & Pages
* Added premium MONETIZE option to Posts & Pages
* Added ability to turn off "Secure Login" feature
* Added ability to toggle extension status (active/inactive)
* Added ability for AAM to filter out Admin Top Bar based on restricted admin menus
* Deprecated AAM Role Filter extension and merged it to the AAM core
* Deprecated AAM Payment extension and merged it with AAM E-Commerce extension
* Deprecated ConfigPress options that manage access to AAM UI. All is based on capabilities from now.
* Split UI to three areas: Access, Settings and Extensions
* Fixed over 25+ reported bugs and discovered during internal refactoring
* Removed deprecated "Security" feature. Replaced with Secure Login Widget
* Removed deprecated "Teaser" feature. Replaced with Teaser Message per post base

= 4.0 =
* Added link Access to category list
* Added shortcode [aam] to manage access to the post's content
* Moved AAM Redirect extension to the basic AAM package
* Moved AAM Login Redirect extension to the basic AAM package
* Moved AAM Content Teaser extension to the basic AAM package
* Set single password for any post or posts in any category or post type
* Added two protection mechanism from login brute force attacks
* Added double authentication mechanism
* Few minor core bug fixings
* Improved multisite support
* Improved caching mechanism

= 3.0 =
* Brand new and much more intuitive user interface
* Fully responsive design
* Better, more reliable and faster core functionality
* Completely new extension handler
* Added "Manage Access" action to the list of user
* Tested against WP 3.8 and PHP 5.2.17 versions

= 2.0 =
* New UI
* Robust and completely new core functionality
* Over 3 dozen of bug fixed and improvement during 3 alpha & beta versions
* Improved Update mechanism

= 1.0 =
* Fixed issue with comment editing
* Implemented JavaScript error catching