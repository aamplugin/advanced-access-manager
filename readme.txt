=== Advanced Access Manager - Restricted Content, Users & Roles, Enhanced Security and More ===
Contributors: vasyltech
Tags: security, access control, user roles, restricted content, api security
Requires at least: 5.8.0
Requires PHP: 5.6.0
Tested up to: 6.8.1
Stable tag: 7.0.5

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

= 7.0.5 =
* Fixed: ConfigPress are not taken into consideration before init hook [https://github.com/aamplugin/advanced-access-manager/issues/468](https://github.com/aamplugin/advanced-access-manager/issues/468)
* Fixed: AAM does not display default terms pin anymore [https://github.com/aamplugin/advanced-access-manager/issues/467] (https://github.com/aamplugin/advanced-access-manager/issues/467)
* Fixed: Uncaught TypeError: array_key_exists(): Argument #2 ($array) must be of type array, null given in /../Framework/Service/Policies.php:661 [https://github.com/aamplugin/advanced-access-manager/issues/466](https://github.com/aamplugin/advanced-access-manager/issues/466)

= 7.0.4 =
* Change: Making sure that all AAM hooks are triggered only after init [https://github.com/aamplugin/advanced-access-manager/issues/465](https://github.com/aamplugin/advanced-access-manager/issues/465)

= 7.0.3 =
* Fixed: The Condition block is not handled properly when Operator is OR [https://github.com/aamplugin/advanced-access-manager/issues/464](https://github.com/aamplugin/advanced-access-manager/issues/464)
* Fixed: Can Not Edit Password Protected Block Pages [https://github.com/aamplugin/advanced-access-manager/issues/463](https://github.com/aamplugin/advanced-access-manager/issues/463)
* Fixed: Uncaught Error: Cannot use object of type WP_Post_Type as array in /../Metaboxes.php on line 383 [https://github.com/aamplugin/advanced-access-manager/issues/461](https://github.com/aamplugin/advanced-access-manager/issues/461)
* Feature Request: Re-introduce the "Unified Multisite Configuration Sync" option [https://github.com/aamplugin/advanced-access-manager/issues/462](https://github.com/aamplugin/advanced-access-manager/issues/462)

= 7.0.2 =
* Fixed: Restricted post with Teaser Message is not enforced [https://github.com/aamplugin/advanced-access-manager/issues/460](https://github.com/aamplugin/advanced-access-manager/issues/460)
* Fixed: The "Redirect to the login page" option does not persist [https://github.com/aamplugin/advanced-access-manager/issues/459](https://github.com/aamplugin/advanced-access-manager/issues/459)
* Fixed: The Reset All AAM settings button does not work [https://github.com/aamplugin/advanced-access-manager/issues/457](https://github.com/aamplugin/advanced-access-manager/issues/457)
* Fixed: Metaboxes for custom taxonomies have the same slug [https://github.com/aamplugin/advanced-access-manager/issues/456](https://github.com/aamplugin/advanced-access-manager/issues/456)
* Fixed: PHP Notice: AAM_Framework_Service_Widgets(): Invalid widget provided in /wp-includes/functions.php [https://github.com/aamplugin/advanced-access-manager/issues/443](https://github.com/aamplugin/advanced-access-manager/issues/443)
* Fixed: AAM labels quote escape [https://github.com/aamplugin/advanced-access-manager/issues/455](https://github.com/aamplugin/advanced-access-manager/issues/455)
* Fixed: List of backend menu items is empty on the Backend Menu tab [https://github.com/aamplugin/advanced-access-manager/issues/454](https://github.com/aamplugin/advanced-access-manager/issues/454)
* Fixed: Issue with clearing buffer [https://github.com/aamplugin/advanced-access-manager/issues/453](https://github.com/aamplugin/advanced-access-manager/issues/453)
* Fixed: Uncaught Error: Call to a member function list() on null in /../Framework/Manager.php:450 [https://github.com/aamplugin/advanced-access-manager/issues/452](https://github.com/aamplugin/advanced-access-manager/issues/452)
* Enhancement: Give the ability to control archive pages [https://github.com/aamplugin/advanced-access-manager/issues/458](https://github.com/aamplugin/advanced-access-manager/issues/458)

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