=== Advanced Access Manager ===
Contributors: vasyltech
Tags: access control, membership, backend menu, user role, restricted content, security, jwt
Requires at least: 4.7.0
Requires PHP: 5.6.0
Tested up to: 5.3.2
Stable tag: 6.3.3

All you need to manage access to WordPress websites on the frontend, backend and API levels for any role, user or visitors.

== Description ==

> Advanced Access Manager (aka AAM) is a powerfully robust WordPress plugin designed to help you control every aspect of your website, your way.

https://www.youtube.com/watch?v=mj5Xa_Wc16Y

= Few Quick Facts =

* The only plugin that gives you the absolute freedom to define a most granular access to any aspect of your website and most of the features are free;
* Bullet-proven plugin that is used on over 100,000 websites where all features are well-tested and [documented](https://aamplugin.com/support). Very low amount of support tickets speaks for quality;
* It is the only plugin that gives you the ability to manage access to your website content for any role, individual user and visitors or even define the default access to all posts, pages, custom post types, categories, and custom taxonomies;
* AAM is a developer-oriented plugin. It has dozens of hooks and configurations. It is integrated with WordPress RESTful and XML-RPC APIs and has numerous abstract layers to simplify coding;
* No ads or other promotional crap. The UI is clean and well crafted so you can focus only on what matters;
* No need to be a "paid" customer to get help. Request support at any time;
* Some features are limited or available only with [premium add-ons](https://aamplugin.com/pricing). AAM functionality is transparent and you will absolutely know when you need to get a premium add-on;

= Main Areas Of Focus =

* [Access & Security Policy](https://aamplugin.com/reference/policy) allows you to define who, when, how and under what conditions your website resources can be accessed;
* Content access control on the frontend, backend and API levels to posts, pages, media attachments, custom post types, categories, tags, custom taxonomies for any role, user and visitors;
* Roles & capabilities management with the ability to create new roles and capabilities, edit, clone or delete existing;
* Access control to backend area including backend menu, toolbar, metaboxes & widgets;
* Access control to RESTful API;
* Developer-friendly API so it can be used by other developers to work with AAM core;
* And all necessary features to setup smooth user flow during login, logout, access denied even, 404 etc.

= The Most Popular Features =

* [free] Manage Backend Menu. Manage access to the backend menu for any user or role. Find out more from [How to manage WordPress backend menu](https://aamplugin.com/article/how-to-manage-wordpress-backend-menu) article;
* [free] Manage Roles & Capabilities. Manage all your WordPress roles and capabilities.
* [free] All necessary set of tools to manage JWT authentication [Ultimate guide to WordPress JWT Authentication](https://aamplugin.com/article/ultimate-guide-to-wordpress-jwt-authentication)
* [free] Create temporary user accounts. Create and manage temporary user accounts. Find out more from [How to create temporary WordPress user account](https://aamplugin.com/article/how-to-create-temporary-wordpress-user-account);
* [limited] Content access. Very granular access to an unlimited number of posts, pages or custom post types ([20 different options](https://aamplugin.com/reference/plugin#posts-terms)). With premium [Plus Package](https://aamplugin.com/extension/plus-package) add-on also manage access to taxonomies, terms or setup the default access to everything. Find out more from [How to manage access to the WordPress content](https://aamplugin.com/article/how-to-manage-access-to-the-wordpress-content) article;
* [free] Manage Admin Toolbar. Filter out unnecessary items from the top admin toolbar for any role or user.
* [free] Backend Lockdown. Restrict access to your website backend side for any user or role. Find out more from [How to lockdown WordPress backend](https://aamplugin.com/article/how-to-lockdown-wordpress-backend) article;
* [free] Secure Login Widget & Shortcode. Drop AJAX login widget or shortcode anywhere on your website. Find out more from [How does AAM Secure Login works](https://aamplugin.com/article/how-does-aam-secure-login-works) article;
* [free] Ability to enable/disable RESTful and XML-RPC APIs.
* [limited] URI Access. Allow or deny access to any page of your website by the page URL as well as how to redirect a user when access is denied;
* [free] Manage access to RESTful or XML-RPC individual endpoints for any role, user or visitors.
* [free] Login with URL. For more information check [WordPress: Temporary User Account, Login With URL & JWT Token](https://aamplugin.com/article/wordpress-temporary-user-account-login-with-url-jwt-token) article.
* [free] Content Filter. Filter or replace parts of your content with AAM shortcodes. Find out more from [How to filter WordPress post content](https://aamplugin.com/article/how-to-filter-wordpress-post-content) article;
* [free] Login/Logout Redirects. Define custom login and logout redirect for any user or role;
* [free] 404 Redirect. Redefine where a user should be redirected when a page does not exist. Find out more from [How to redirect on WordPress 404 error](https://aamplugin.com/article/how-to-redirect-on-wordpress-404-error);
* [free] Access Denied Redirect. Define custom redirect for any role, user or visitors when access is denied for a restricted area on your website;
* [free] Manage Metaboxes & Widgets. Filter out restricted or unnecessary metaboxes and widgets on both frontend and backend for any user, role or visitors. Find out more from [How to hide WordPress metaboxes & widgets](https://aamplugin.com/article/how-to-hide-wordpress-metaboxes-and-widgets) article;
* [paid] Manage access based on IP address or referred domain. Manage access to the entire website or any specific page or post based on referred host or IP address. Find out more from [How to manage access to WordPress website by IP address](https://aamplugin.com/article/how-to-manage-access-to-wordpress-website-by-ip-address) article;
* [free] Multiple role support. AAM supports multiple roles per user [WordPress access control for users with multiple roles](https://aamplugin.com/article/wordpress-access-control-for-users-with-multiple-roles)
* [and even more...] Check our [official website](https://aamplugin.com) to learn more about AAM

= Non-Negotiable =

We take security and privacy very seriously, that is why there are several non-negotiable items that we obey for all cost in the basic AAM version.

* AAM does not create new or alter existing website database tables;
* AAM does not read any files outside of the AAM plugin’s folder;
* AAM does not create new, write or delete any existing files or folders on a server;
* AAM does not capture or send externally any information about how it is used;
* AAM does not capture or send externally any information about a website server. The only exception is a website domain that is assigned to a premium license during activation;
* AAM does not integrate with any other plugins directly;
* AAM does not impersonate or swap user login sessions. All the authentication is handled by WordPress core where AAM may provide only verified and trusted information as means of authentication;
* AAM does not include advertisement of any kind (no banners, cross-sales pitches or affiliate links);

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

= 6.3.3 =
* Change: Updated core to allow geolocation functionality with IP Check
* Change: Enhanced [IP Check](https://aamplugin.com/pricing/ip-check) add-on with ability to define geolocation rules [https://aamplugin.com/article/how-to-manage-access-to-wordpress-website-based-on-location](https://aamplugin.com/article/how-to-manage-access-to-wordpress-website-based-on-location)
* Change: Enhanced [Plus Package](https://aamplugin.com/pricing/plus-package)

= 6.3.2 =
* Fixed Bug: *_OTHERS posts & terms access options malfunction [https://github.com/aamplugin/advanced-access-manager/issues/52](https://github.com/aamplugin/advanced-access-manager/issues/52)

= 6.3.1 =
* Fixed Bug: Draft policy still applicable if attached to user or role [https://github.com/aamplugin/advanced-access-manager/issues/49](https://github.com/aamplugin/advanced-access-manager/issues/49)
* Fixed Bug: Resetting all AAM settings still keep legacy settings in DB [https://github.com/aamplugin/advanced-access-manager/issues/48](https://github.com/aamplugin/advanced-access-manager/issues/48)
* Fixed Bug: PHP Warning: Invalid argument supplied for foreach() in .../Repository.php on line 71 [https://github.com/aamplugin/advanced-access-manager/issues/47](https://github.com/aamplugin/advanced-access-manager/issues/47)
* Fixed Bug: User's capabilities, populated through policy, are gone when rebased [https://github.com/aamplugin/advanced-access-manager/issues/45](https://github.com/aamplugin/advanced-access-manager/issues/45)
* Fixed Bug: Cannot lock user with AAM UI [https://github.com/aamplugin/advanced-access-manager/issues/43](https://github.com/aamplugin/advanced-access-manager/issues/43)
* Fixed Bug: Teaser Message modified with added backslashes to single and double quotes [https://github.com/aamplugin/advanced-access-manager/issues/42](https://github.com/aamplugin/advanced-access-manager/issues/42)

= 6.3.0 =
* Fixed Bug: PHP Notice about missing license key [https://github.com/aamplugin/advanced-access-manager/issues/12](https://github.com/aamplugin/advanced-access-manager/issues/12)
* Fixed Bug: Fatal error: Allowed memory size of XXX bytes exhausted [https://github.com/aamplugin/advanced-access-manager/issues/15](https://github.com/aamplugin/advanced-access-manager/issues/15)
* Fixed Bug: PHP Notice: Undefined index: path [https://github.com/aamplugin/advanced-access-manager/issues/18](https://github.com/aamplugin/advanced-access-manager/issues/18)
* Fixed Bug: PHP Notice: Undefined index: password [https://github.com/aamplugin/advanced-access-manager/issues/31](https://github.com/aamplugin/advanced-access-manager/issues/31)
* Fixed Bug: NGIX compatibility for URI Access [https://github.com/aamplugin/advanced-access-manager/issues/33](https://github.com/aamplugin/advanced-access-manager/issues/33)
* Fixed Bug: URI Access service does not protect the homepage [https://github.com/aamplugin/advanced-access-manager/issues/17](https://github.com/aamplugin/advanced-access-manager/issues/17)
* Fixed Bug: New rule is created if URI Access endpoint is updated [https://github.com/aamplugin/advanced-access-manager/issues/35](https://github.com/aamplugin/advanced-access-manager/issues/35)
* Fixed Bug: Conflict with Jatpack plugin [https://github.com/aamplugin/advanced-access-manager/issues/25](https://github.com/aamplugin/advanced-access-manager/issues/25)
* Fixed Bug: Potentially incorrectly used PHP core `list` function [https://github.com/aamplugin/advanced-access-manager/issues/38](https://github.com/aamplugin/advanced-access-manager/issues/38)
* Added New: Access Policy token [PHP_GLOBAL](https://aamplugin.com/reference/policy#php_global)
* Added New: Access Policy token [WP_NETWORK_OPTION](https://aamplugin.com/reference/policy#wp_network_option)
* Added New: Allow to attach Access Policies to Default subject [https://github.com/aamplugin/advanced-access-manager/issues/13](https://github.com/aamplugin/advanced-access-manager/issues/13)
* Added New: Ability to create new access policy from generated [https://github.com/aamplugin/advanced-access-manager/issues/27](https://github.com/aamplugin/advanced-access-manager/issues/27)

= 6.2.2 =
* Fixed Bug: Backend Dashboard index.php still could be restricted with Backend Menu service
* Fixed Bug: Policy Generator - Fatal error with PHP lower than 7.0.0
* Fixed Bug: Policy Validator - Improper dependency validation when if it is not installed
* Fixed Bug: Default access settings not propagated to user that does not have any roles (multisite setup)
* Fixed Bug: Reset settings where not synced across all subsites in multisite setup
* Added New: Ability to define wildcard [BackendMenu](https://aamplugin.com/reference/policy#backendmenu) resource with Access Policy
* Added New: Ability to define wildcard [Metabox](https://aamplugin.com/reference/policy#metabox) resource with Access Policy
* Added New: Ability to define wildcard [Widget](https://aamplugin.com/reference/policy#widget) resource with Access Policy
* Added New: Ability to define wildcard [Toolbar](https://aamplugin.com/reference/policy#toolbar) resource with Access Policy

= 6.2.1 =
* Fixed Bug: Very minor UI issue with Access Policy Delete pop-up
* Added New: Enhanced Access Policy with new [POLICY_META](https://aamplugin.com/reference/policy#policy_meta) token
* Change: Access Policy post type supports custom fields now

= 6.2.0 =
* Fixed Bug: Access policy was not applied to visitors
* Fixed Bug: Bug fixing that is related to unwanted PHP notices [https://forum.aamplugin.com/d/456-notice-undefined-index-expire](https://forum.aamplugin.com/d/456-notice-undefined-index-expire)
* Fixed Bug: Failing to delete multiple Access URI rules without reloading the page
* Added New: Ability to generate Access Policy from user's or role's settings [https://forum.aamplugin.com/d/446-announcement-about-upcoming-features/2](https://forum.aamplugin.com/d/446-announcement-about-upcoming-features/2)
* Added New: More granular control over the HIDDEN access option [https://forum.aamplugin.com/d/446-announcement-about-upcoming-features](https://forum.aamplugin.com/d/446-announcement-about-upcoming-features)
* Added New: Export/Import AAM settings [https://aamplugin.com/article/how-to-export-and-import-aam-settings](https://aamplugin.com/article/how-to-export-and-import-aam-settings)
* Added New: Ability to send support request from the AAM UI
* Added New: Multisite Settings Sync service that allows to sync access settings changes across all sites
* Added New: New hook `aam_updated_access_settings` that is triggered when access settings are stored
* Added New: New data type casting (*date) for Access Policy [https://aamplugin.com/reference/policy#markers](https://aamplugin.com/reference/policy#markers)
* Added New: New POLICY_PARAM access policy token [https://aamplugin.com/reference/policy#policy_param](https://aamplugin.com/reference/policy#policy_param)
* Added New: New WP_SITE access policy token [https://aamplugin.com/reference/policy#wp_site](https://aamplugin.com/reference/policy#wp_site)
* Change: [DATETIME](https://aamplugin.com/reference/policy#marker-datetime) access policy token returns time in UTC timezone
* Change: Enhanced security over AAM UI
* Change: Multiple internal simplifications and refactoring

= 6.1.1 =
* Fixed Bug: Unnecessary backslashes before displaying the access policy [https://forum.aamplugin.com/d/432-access-policy-ui-escaping-slashes](https://forum.aamplugin.com/d/432-access-policy-ui-escaping-slashes)
* Fixed Bug: aam_access_dashboard custom capability caused "Access Denied"
* Change: Enforcing default `307` Temporary Redirect code if none is provided for any AAM redirect functionality
* Change: Persisting the last managed role, user or visitor on the AAM page
* Change: Improved safety by using the last role on the list instead of the default Administrator role
* Change: Optimized access policy service. Changed the way it is applied to any given object
* Added New: Migration script that clears previously detected migration errors

= 6.1.0 =
* Fixed Bug: Access Policy UI - the "Attach to Default" button was not rendering correctly
* Fixed Bug: Role Management UI - the PHP notice where `Undefined variable: parent`
* Fixed Bug: AAM UI page - improperly compressed HTML response if server config does not match PHP executable INI settings
* Fixed Bug: Login Redirect Settings - incorrectly merged settings for multi-role support
* Fixed Bug: Logout Redirect Settings - incorrectly merged settings for multi-role support
* Fixed Bug: Access Denied Redirect Settings - incorrectly merged settings for multi-role support
* Fixed Bug: API Route Settings - incorrectly halted inheritance mechanism
* Fixed Bug: Admin Toolbar Settings - incorrectly halted inheritance mechanism
* Fixed Bug: URI Access Settings - incorrectly halted inheritance mechanism
* Fixed Bug: Content Visibility Settings - incorrectly merged settings for multi-role support
* Fixed Bug: Access Policy Core - incorrectly managed internal cache
* Fixed Bug: AAM Core - incorrectly managed internal object cache
* Fixed Bug: Content Service - incorrectly mapped `do_not_allow` capability if any of the registered post types have it
* Fixed Bug: Content Service - fatal error `Cannot use object of type Closure as array` [https://forum.aamplugin.com/d/354-php-fatal-error-cannot-use-object-of-type-closure-as-array](https://forum.aamplugin.com/d/354-php-fatal-error-cannot-use-object-of-type-closure-as-array)
* Fixed Bug: The `aam_show_toolbar` capability was not taken in consideration
* Fixed Bug: Logout Redirect Service - White screen occurs if "Default" option is explicitly selected [https://wordpress.org/support/topic/blank-log-out-page-on-6-0-5/](https://wordpress.org/support/topic/blank-log-out-page-on-6-0-5/)
* Change: Refactored internal inheritance mechanism where AAM objects no longer responsible to check for inheritance flag. This eliminates several constrains that we discovered recently.
* Change: Multiple minor changes to the codebase to consume internal AAM API in more consistent way
* Change: JWT & Secure Login Services - enriched RESTful API error responses with more details about an error
* Change: Content Service - optimization improvements
* Added New: Implemented new filter `aam_token_typecast_filter` for Access Policy for custom type casting
* Added New: Implemented support for the `=>` (map to) operator for the Access Policy
* Added New: Implemented support for the AAM_CONFIG marker for the Access Policy

= 6.0.5 =
* Fixed Bug: Refactored the license managements. Fixed bugs with license registration https://forum.aamplugin.com/d/356-unregistered-version-message
* Fixed Bug: Some servers do not allow WP core string concatenation. This was causing 403 https://forum.aamplugin.com/d/389-message-loading-aam-ui-please-wait-403-forbidden
* Fixed Bug: Media list on Posts & Terms tab is not rendered correctly due to improperly managed DB query for post type `attachment`
* Fixed Bug: AAM core getOption method did not deserialized settings properly in some cases
* Fixed Bug: Access Manager metabox was rendered for users that have ability to manage other users https://forum.aamplugin.com/d/371-you-are-not-allowed-to-manage-any-aam-subject
* Fixed Bug: Logout redirect was no working properly https://forum.aamplugin.com/d/339-problem-with-login-shortcode-and-widget
* Fixed Bug: The Drill-Down button was not working on Posts & Terms tab
* Fixed Bug: Access policy Action "Create" was not converted at all for the PostType resource
* Change:    Simplified the first migration script by removing all error emissions. We captured enough migration logs to be confident about proper migration of the most critical settings
* Change:    Changed verbiage for the Enterprise Package on the Add-ons area
* Change:    Added info notification to the Posts & Terms tab for proper Media access controls
* Change:    Merge internal Settings service with Core service
* Change:    Added new migration script that fixed issues with legacy names for premium add-ons
* Change:    Added new internal AddOn manager class
* Added New: Added the ability to check for new add-on updates from the Add-ons area
* Added New: Published free AAM add-on AAM Protected Media Files https://wordpress.org/plugins/aam-protected-media-files/

= 6.0.4 =
* Fixed Bug: https://forum.aamplugin.com/d/367-authentication-jwt-expires-fatal-error
* Fixed Bug: JWT validation endpoint did not check token's expiration based on UTC timezone
* Fixed Bug: Removed unnecessary console.log invocations from the aam.js library
* Fixed Bug: Fixed the potential bug with improperly merged options when access policy Param's Value is defined as multi-dimensional array
* Fixed Bug: https://forum.aamplugin.com/d/339-problem-with-login-shortcode-and-widget
* Fixed Bug: https://forum.aamplugin.com/d/371-you-are-not-allowed-to-manage-any-aam-subject
* Fixed Bug: Incompatibility with plugins that are extremely aggressive and modify the WP_Query "suppress_filters" flag. Shame on you guys!

= 6.0.3 =
* Fixed Bug: Fatal Error - Class 'AAM_Core_Server' not found. https://forum.aamplugin.com/d/358-uncaught-error-class-aam-core-server-not-found
* Fixed Bug: Fixed the bug where post types that do not have Gutenberg enabled are not shown on the Metaboxes & Widgets tab https://wordpress.org/support/topic/in-metaboxes-widgets-no-pages/
* Fixed Bug: Not all possible post types are shown on the Posts & Terms tab

= 6.0.2 =
* Fixed Bug: https://forum.aamplugin.com/d/361-uncaught-error-call-to-a-member-function-settimezone-on-boolean
* Fixed Bug: https://forum.aamplugin.com/d/378-aam-6-0-1-conflict-with-acf-advanced-custom-fields
* Fixed Bug: Migration script, fixed couple more minor bugs that were causing warnings

= 6.0.1 =
* Fixed Bug: Numerous bugs fixed in the migration script. New script prepared to do additional clean-up and fix corrupted data
* Fixed Bug: https://forum.aamplugin.com/d/369-notice-undefined-offset-1-service-content-php-on-line-509
* Fixed Bug: https://wordpress.org/support/topic/6-0-issues/
* Fixed Bug: https://forum.aamplugin.com/d/353-comment-system-activated
* Fixed Bug: Migration script was skipping access settings conversion for roles that have white space in slug
* Added New: Additional migration script for clean-up and fixing corrupted data

= 6.0.0 =
* Complete rewrite of the entire plugin. For more information, check [this article](https://aamplugin.com/article/advanced-access-manager-next-generation)

= 5.9.7.1 =
* Fixed the bug with Access Policy for Capability resource
* Fixed the bug with Nginx redirect rules for media access

= 5.9.7 =
* Prep for upcoming AAM v6 release. Converting all extensions to plugins
* Covered odd use-case when some plugins decide to register CPT capabilities during plugin activation
* Improved Backend Menu feature functionality

= 5.9.6.3 =
* Fixed the bug with merging access settings for multiple roles
* Improved the way capabilities are managed internally by AAM
* Fixed PHP notice reported by jaerlo https://forum.aamplugin.com/d/207-indirect-modification-of-overloaded-property-aam-core-subject-user-roles
* Fixed PHP fatal error reported by kevinagar https://wordpress.org/support/topic/fatal-error-3199/
* Fixed the bug with Backend Menu feature where all the menu items that require "administrator" capability where not shown

= 5.9.6.2 =
* Fixed the bug added slashes to the Access Policy JSON document
* Fixed the bug with Metaboxes & Widgets to prevent PHP warning for widgets that registered with Closure callback
* Fixed the bug in URI Access feature that causes PHP warning when data is merged for multiple roles
* Fixed the bug with Access Policy rules that are not initialized correctly for Visitors
* Fixed the bug reported on GitHub https://github.com/aamplugin/advanced-access-manager/issues/6
* Changed the way AAM hooks into get_options pipeline with Access Policy "Params". This is done to support array options
* Changed the way Login Widget is registered to reduce code

= 5.9.6.1 =
* Fixed the fatal error related to URI object

= 5.9.6 =
* Fixed the bug with URI Access feature for URIs with trailing forward slash "/"
* Fixed the bug with Access Policy where incorrect default value was propagated
* Fixed the bug with API Routes not merged properly with multiple-roles support
* Added HTTP Redirect Code to URI Access, Posts & Terms features
* Added new Access Policy marker type QUERY that is alias for the GET
* Added support for the null data type for Access Policy data type casting
* Improved the way password-protected feature works; enhanced Access Policy to support it https://aamplugin.com/reference/policy#post
* Deprecated and removed internal AAM cache by optimizing AAM performance. Cache became major constrain for the dynamic Access Policy conditions

= 5.9.5 =
* Fixed the bug with Access Policy `Param` value that was not evaluating embedded markers
* Fixed the bug that was causing PHP Warning for users that have none-existing role assigned
* Fixed the bug with Customizer that was blocking user from publishing changes
* Added support for `tags` - the ability to manage access to posts by none-hierarchical terms
* Added the ability to define dynamic Resource names with markers in Access Policies
* Added new Access Policy marker USERMETA https://aamplugin.com/reference/policy#usermeta

= 5.9.4 =
* Fixed the bug with incorrectly identifying CPT capabilities
* Fixed the bug with URI Access where there where no way to override wildcard rule
* Fixed multiple bugs related to JWT authentication
* Fixed the bug with Access Policy that triggers PHP Notice for visitors
* Removed support for ConfigPress option `core.settings.setJwtCookieAfterLogin`
* Added the ability to obtain Login URL from the "Manage User" modal
* Added the ability to control AAM cache size https://aamplugin.com/reference/plugin#core-cache-limit
* Refactored Capabilities feature to follow the best practices for integration with WP Core
* Refactored JWT authentication so it can be more seamlessly integrated with user status

= 5.9.3 =
* Fixed the bug with LIST and LIST TO OTHERS options for multiple roles support
* Fixed the bug with managing access to custom post types that contain "-" in name
* Added ability to refresh JWT token with new RESTful endpoint /refresh-jwt
* Added ability to filter out metabox by its name with Access Policy
* Improved Posts & Terms access control with Access Policy

= 5.9.2.1 =
* Fixed several bugs that are related to post, page or custom post type editing

= 5.9.2 =
* Fixed the bug with Access Policy access control
* Fixed the bug with Access Policy tab shows only 10 last Policies
* Fixed the bug where AAM was not determining correct max user level
* Fixed the bug where user was able to manage his roles on the profile page
* Fixed the bug with Access Policy "Between" condition
* Optimized AAM to support unusual access capabilities for custom post types https://forum.aamplugin.com/d/99-custom-post-type-does-not-honor-edit-delete-publish-overrides/5
* Enhanced Access Policy with few new features. The complete reference is here https://aamplugin.com/reference/policy
* Enabled 'JWT Authentication' by default
* Significantly improved AAM UI page security
* Added new JWT Tokens feature to the list of AAM features https://aamplugin.com/reference/plugin#jwt-tokens
* Added new capability aam_manage_jwt
* Added "Add New Policies" submenu to fix WordPress core bug with managing access to submenus
* Removed "Role Expiration" feature - it was too confusing to work with
* Removed allow_ajax_calls capability support - it was too confusing for end users

= 5.9.1.1 =
* Fixed the bug with saving Metaboxes & Widgets settings
* Fixed the bug with saving Access Policy that has backward slashes in it
* Fixed the bug with fetching Param values from the Access Policies
* Fixed the bug with Access Policy resource "Role" when Effect is set to "deny"
* Adjusted AAM core to prevent PHP warning when edit_user or delete_user capability is checked without user ID provided (caused by other plugins)

= 5.9.1 =
* Fixed the bug with controlling which capability can be deleted with Access Policy
* Fixed typo in the aam_edit_others_policies capability slug
* Fixed the bug with API Routes not being saved property for those that have htmlspecial characters in it
* Fixed major bug with keeping track of active user sessions that prevents multiple session per same user
* Added "Redirect To Login" to the LIMIT option for visitors on Posts & Terms tab
* Added the new concept of "Boundary" to Access Policy that allows to Enforce certain statements

= 5.9 =
* Fixed the bug with publish pages not being managed correctly
* Fixed the bug with getting correct post from the list of posts
* Significantly enhanced AAM UI security
* Added ability to toggle default term for any post type
* Added ability to assign multiple roles per user

= 5.8.3 =
* Fixed the bug with multi-lingual support
* Fixed the bug with LIMIT option that escaped quotes in the message
* Fixed the bug with managing access to Access Policies
* Added support for aam_edit_policy, aam_read_policy, aam_delete_policy, aam_delete_policies, aam_edit_policies, aam_edit_others_policies, aam_publish_policies capabilities
* Refactored Default Category functionality (moved it to Plus Package extension)
* Added support for the nav_menu_meta_box_object hook to filter posts on Menu Builder page
* Extend Access Policy with more features

= 5.8.2 =
* Fixed numerous bugs with access control for media
* Added support for change_own_password capability
* Added support for change_passwords capability
* Added type casting to the Access Policy document
* Added new resource `Role` to the Access Policy
* Refactored internal Access Policy implementation
* Improved performance

= 5.8.1 =
* Fixed bug that causes fatal error with Policy editor on Linux servers
* Profiled and improved several bottlenecks that may speed-up website load up to 300 milliseconds

= 5.8 =
* Fixed the bug with Access Policy settings inheritance mechanism
* Fixed numerous of bugs with JWT authentication and improved time expiration handling
* Enhanced temporary user access management functionality
* Added Logout action when user access expired
* Added ability to login user with URL

= 5.7.3 =
* Fixed the bug with JWT authentication
* Fixed the PHP bug in policy metabox when no errors with JSON is detected
* Fixed the bug with license expiration for Extended versions not properly displayed
* Fixed the bug with Admin Menu listed incorrectly when Default Access Settings defined
* Fixed the PHP bug in Post object when access settings are defined in a Policy
* Improved role creation feature
* Improved capability handling with Access & Security Policy
* Refactor the way extension is installed to eliminate cURL issues
* Deprecated and removed `aam_display_license` capability
* Extended default policy document with dependencies
* Added support for `Features` in the Access & Security Policy
* Added policy Validation functionality
* Reduced number of methods that use cURL to contact aamplugin.com API

= 5.7.2 =
* Fixed bug with Posts & Terms feature for WP version under 4.8
* Fixed bug were Access Policy can't be attached to any principal on the Policy edit screen
* Fixed bug with Access URI options were not merged for users with multiple roles
* Fixed bug with Access URI options were not exported
* Fixed but with Post PUBLISH option due to the fact that Gutenberg is using RESTful API
* Extended Access & Security Policy to support Posts & Terms options
* Added /validate-jwt RESTful API endpoint to validate JWT
* Added ability to extract JWT token from GET queries or POST payload
* Added custom capability aam_view_help_btn to hide HELP icon on AAM UI
* Significantly improved capability mapping mechanism and access control based on caps
* Added URI Access support to Access & Security Policy
* Added Post, Term, PostType support to Access & Security Policy

= 5.7.1 =
* Fixed the bug with AAM notifications related to extension updates
* Fixed the bug with AAM not taking in consideration capabilities that defined in policy
* Improved the way show_admin_bar capability is handled
* Added ability to define Conditions to the Statement Policy document

= 5.7 =
* Added a huge innovation to the access control management - Access & Security Policy
* Fixed the bug with updating extension versions

= 5.6.1.1 =
* Fixed the bug when website may crash when some extensions are really out-of-date

= 5.6.1 =
* Fixed the bug with caching
* Fixed the bug with the way post type and taxonomies are registered with extensions
* Turned on by default the ability to edit and delete capabilities

= 5.6 =
* Fixed the bug with encoding on Safari when gzip is enabled
* Fixed the bug with double caching
* Added URI Access feature that allows to manage access to any website URI
* Improved UI a little bit

= 5.5.2 =
* Improved performance for website with large amount of posts/pages
* Prepared few changes forward for the upcoming AAM 5.6 release

= 5.5.1 =
* Fixed the bug with exporting AAM settings when roles, configpress was added by default
* Fixed the bug with AAM cache not being triggered properly
* Fixed the bug with some of the classes been cached improperly
* Fixed the bug with toolbar filter that are corrupted by third part plugin or theme
* Improved AAM to handle gzip compression properly
* Updated bootstrap library to the v3.3.7

= 5.5 =
* Fixed the bug with EDIT BY OTHERS option
* Fixed UI bug when managing access to AAM page itself
* Fixed the bug reported by https://github.com/KenAer
* Fixed the bug with creating new post when default access is denied to EDIT
* Fixed the bug with editing page that is in draft state
* Fixed multiple bugs with AAM export/import feature
* Fixed the bug with blocked user being able to login again
* Slightly improved extension installation feedback
* Improved UI
* Enhanced JWT token feature
* Improved the way Backend Menu and Toolbar features work
* Added multiple-roles support
* Refactored Import/Export features
* Removed Settings->Tools tab

= 5.4.3.2 =
* Fixed bug that incorrectly checks post author property
* Fixed bug that does not allow to assign roles that contain apostrophe
* Fixed bug with incorrectly handled AAM Console messages that contain HTML
* Added ability to order roles by name
* Added ability to order users by display name
* Improved Users/Roles Manager UI

= 5.4.3.1 =
* Quick fix for the bug that is related to Posts & Terms

= 5.4.3 =
* Fixed the bug with Posts & Terms feature that hides it when Manage Frontend & Backend Access are disabled however API is enabled
* Fixed the bug that cached objects while managing them on AAM page. That was causing inconsistency sometimes
* Fixed the bug with content shortcode that was defining incorrectly if wrapped content should be hidden or not for specific user
* Fixed the bug with AAM not being able to apply translations for other languages
* Added new option "Support AAM Extensions" that allows to enables/disables Extensions feature
* Added new option "Get Started Feature" that toggle the Get Started tab
* Added new option "AAM Cron Job" that enables/disables the internal AAM cron job
* Added Get Started tab with some basic introduction to AAM plugin
* Added ability to set "hard" user login time
* Added ability to sort posts and terms list by title
* Enhanced JWT authentication with ability to set also cookie that contains JWT token or define signing algorithm
* Refactored Metaboxes & Widget feature so initialization process is handled with client side
* Refactored Admin Toolbar feature so initialization process is handled with client side
* Improved the Post & Terms feature by enabling to manage more post types out-of-box
* Improved the Import/Export feature that eliminates issues with incompatible AAM versions
* Refactored internal implementation to make it compatible with strict and secure environments like WordPress VIP

= 5.4.2 =
* Fixed the bug that was causing an error with legacy "teaser" data
* Fixed the bug with aam_manage_admin_toolbar capability been named incorrectly
* Clearing all AAM settings when plugin is uninstalled
* Highlighted post, term or type that has explicit access settings defined on Posts & Terms tab
* Improved JWT authentication feature to allow use it for stand-alone embedded to WP apps

= 5.4.1 =
* Fixed the bug reported by Doug Davis where newly created posts get locked if default access settings are defined
* Fixed the bug with post visibility when /%category%/%postname%/ permalink is defined
* Fixed the but with default category not been selected when redefined with ConfigPress
* Improved AAM performance by caching post visibility results

= 5.4 =
* Fixed bug with Api Access Control option that when disabled, still denies API Routes
* Fixed bug when RESTful or XML-RPC disabled but endpoints still listed on API Routes
* Fixed bug with Secure Login for themes that are not build with jQuery support
* Fixed bug with posts not been filtered during search in few post types
* Added ability to manage Admin Toolbar items
* Added ability to manage premium licenses so now user can transfer license anytime
* Moved security options (brute force lockout, login timeout etc) to stand-alone Security tab
* Improved UI for the ACCESS EXPIRATION option on Posts & Terms tab
* Improved UI for defining temporary user account timespan
* Removed deprecated "Check Post Visibility" option

= 5.3.5 =
* Fixed bug with post LIST & LIST TO OTHERS when access is set to term in odd order
* Fixed bug that potentially did not filter posts during search
* Added notification to the UI that extension folder does not exist or is not writable
* Added XML-RPC endpoint control
* Added ability to filter list of users by roles on the Users/Roles Manager panel

= 5.3.4 =
* Fixed incompatibility issue with plugins that use "plugins_loaded" hook for post manipulations
* Fixed the bug with AAM_Api_Rest_Resource_User
* Fixed issues with ConfigPress settings compatibility between versions
* Fixed the issues with infinite loop when access denied redirect is not configured correctly
* Fixed issue with post filtering that disregards Backend/Frotent/API Access Control settings
* Fixed bug with login widget labels
* Added more information about parent terms & posts to the Post & Terms list
* Added additional widget that lists of AAM licenses on the Extensions tab
* Added fallback secret key for jwt token generator
* Added ability to filter out widgets from the Appearance->Widgets screen

= 5.3.3 =
* Fixed couple bugs with secure login widget rendering
* Fixed the bug with AAM UI refresh triggered by aam extensions
* Fixed the bug with send remote request and array of cookies
* Added ability to hide login navigations links in the secure login widget with feature.secureLogin.ui.showNav configPress option
* Added new custom capability "manage_same_user_level"

= 5.3.2 =
* Fixed the bug that triggers PHP warnings when blocked user is trying to login
* Fixed the bug with get current post method in the core API
* WARNING Experimental approach! to the post access that enormously improve AAM performance
* Added custom capability "edit_permalink" that control ability to edit post permalink

= 5.3.1 =
* Fixed bug with deprecated cache object to keep it backward compatible
* Fixed bug with teaser message on none latin alphabet
* Improved REDIRECT functionality for Posts & Terms feature
* Added finally singe point API (AAM::api method)
* Added "Single Session" option to the Secure Login widget
* Added more localization string to the AAM *.po file
* Standardized AAM core settings names
* Standardized REST API error codes

= 5.3 =
* Fixed the bug with ConfigPress settings when array is defined
* Fixed the bug with jwt authentication
* Fixed the bug with infinite logout loop when user is locked
* Refactored internal functionality to make it fully compatible with WP REST API
* Split Posts & Pages access control on Backend, Frontend and API sections
* Cleaned up posts and pages access settings
* Refactored internal AAM cache to make it more flexible and faster
* Added "API Access Control" option
* Added ability to change user role after certain period of time
* Removed ability to lock Dashboard menu

= 5.2.7 =
* Fixed bug with REST API Routes list
* Improved REST API response messages
* Added support for WordPress RESTful API for posts, categories, comments and users.

= 5.2.6 =
* Dropped support for WordPress versions 3.x. Min supported version is 4.0
* Fixed bug with Admin Menu access control to Posts list
* Fixed bug in AAM Core API for get plugins data call
* Fixed bug with visitors cache auto-flush
* Minor improvements to the AAM UI

= 5.2.5 =
* Fixed the bug with JWT authentication
* Added the ability to enable/disable XML-RPC
* Added the ability to enable/disable REST API
* Added the ability to manage access to the individual REST API endpoints

= 5.2.1 =
* Fixed bug with Linux incompatibility

= 5.2 =
* Fixed the bug with user lock functionality
* Dropped support for PHP 5.2.x version. Minimum required version is 5.3.0
* Merged ConfigPress extension to the core
* Added JWT Authentication
* Added Register link to the Secure Login Widget

= 5.1.1 =
* Fixed the issue with Multisite Network notification
* Fixed the minor bug with login message for "Redirect to login form"
* Deleted redundant AAM_Core_Log class
* Improved and refactored AAM Core Login functionality for upcoming REST API control extension

= 5.1 =
* Fixed sever minor bugs reported by users
* Added free social login extension (alpha version undocumented)
* Added ability to create a temporary user account
* Moved all free extension to the Github repository

= 5.0.8 =
* Fixed the bug to keep AAM compatible with older WP version

= 5.0.7 =
* Fixed the bug that is caused by other plugins not using core filters correctly
* Hiding Dashboard and Edit My Profile links if user does not have access to them

= 5.0.6 =
* Fixed several minor PHP errors caused by legacy PHP versions and corrupted data
* Another boost to the AAM performance
* Normalized few AAM core filters and actions

= 5.0.5 =
* Enhanced Admin Menu feature
* Extended AAM API. Preparing it for developers to use.

= 5.0.4 =
* Fixed bug with caching. Significantly improved speed.
* Fixed incompatibility issue with websites that have corrupted role list.
* Fixed bug with role expiration timer when "Manage Backend Access" option is off.
* Fixed incompatibility issue with plugins that use "the_title" filter.
* Fixed bug with extension status
* Removed registration step during plugin activation.

= 5.0.3 =
* Fixed bug with LIST option
* Fixed bug with incompatible PHP 5.3 or lower

= 5.0.2 =
* Fixed bug with admin menu reported by Andrew
* Fixed possible bug with theTitle filter
* Fixed bug with custom HTML message for the access denied redirect rule
* Fixed bug with ACCESS EXPIRATION option for Posts & Pages
* Fixed bug with Multinetwork setup when super admin is not able to add new users
* Fixed bug with extension statuses
* Removed support for integration with ConfigPress plugin. Use ConfigPress extension instead
* Added localization strings for Login widget & shortcode

= 5.0.1 =
* Fixed bug with extension updates status
* Fixed bug in post core handling caused by incompatibility with unknown plugin
* Improved UI notification with more insides about the issue

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

= 4.9.5.2 =
* Fixed compatibility with PHP 5.3 or lower

= 4.9.5.1 =
* Fixed the bug with media access
* Improved UI

= 4.9.5 =
* Improved user experience with AAM UI
* Removed Welcome message
* Fixed bug with media access
* Added filter for AAM shordcodes so other plugins can hook to AAM
* Optimized AAM javascript
* Removed subscription box to reduce "UI noise" as more features are coming

= 4.9.4 =
* Significantly improved Admin Menu access management
* Filter AAM UI based on Backend/Frontend Access Control options

= 4.9.3 =
* Simplified core implementation. First iteration to upcoming v5.0
* Added ability to check for extension updates with "Check for Updates" button
* Adjusted Admin Menu access control to cover none-standard menu definitions
* Multiple improvements to the UI
* Fixed bug with enter key not working with Login Widget
* Improved cache implementation to cover scenario when user manually corrupted cache data
* Fixed bug with utilities compatibility
* Fixed bug with extended license key
* Fixed bug with LIST and READ options checked at the same time that causes 404
* Extended Import/Export feature to cover multisite network sync
* Added ability to sync settings between multisite network

= 4.9.2 =
* Fixed the bug with AAM media control for files with special characters
* Added secure login widget and shortcode
* Deprecated Security feature

= 4.9.1 =
* Improved UI
* Improved [aam] shortcode
* Improved plugin activation experience

= 4.9 =
* Fixed bug with Login Redirect duplicate settings saving
* Added ability to hide license key with aam_display_license capability
* Added ability to export/import AAM settings
* Improved AAM UI
* Added ability to restrict access to the Hope page
* Added ability to manage access to frontend ajax calls with allow_ajax_calls cap

= 4.8.1 =
* Added ability to control post_password_expires with post.password.expires config
* Improved media access
* Improved UI

= 4.8 =
* Fixed the bug with Media access control reported by Antonius Hegyes
* Fixed the bug with post access properties preview
* Fixed the bug with permanent redirects cached by some browsers
* Fixed the bug with PasswordHash fatal error
* Added ability to define teaser message for an individual post or category
* Deprecated Content Teaser tab (will be removed in AAM 5.0)
* Extended [aam context="content"] shortcode to filter content based on IP address
* Added ability to set time expiration for roles

= 4.7.6 =
* Added ability to hide admin notification with show_admin_notices capability
* Added ability to subscribe to the AAM updates
* Updated refund policy term

= 4.7.5 =
* Improved Utilities tab
* Fixed bug with post search and archive pages
* Updated localization source

= 4.7.2 =
* Fixed the bug with Posts & Pages pagination feature
* Fixed the bug with Media access control
* Improved UI
* Added Welcome email message to every new AAM installation

= 4.7.1 =
* Fixed the PHP bug reported by CodePinch service
* Fixed the bug with Posts & Pages redirect URL
* Fixed the bug related to extensions update status
* Optimized cron procedure for AAM maintenance needs
* Added ability to restore default capabilities for users
* Move AAM User Activity to the free extension suite
* Introduced Development Package for unlimited number of sites

= 4.7 =
* Significantly improved the ability to manage access to AAM interface
* Added new group of capabilities AAM Interface
* Optimized Posts & Pages UI feature for extra large amount of records
* BIGGEST DEAL! From now no more 10 posts limit. It is unlimited!
* Fixed bug with custom HTML message for access denied redirect
* Added option to redirect to login page and back after login when access is denied
* Significantly improved media access control
* Improved CSS to keep to suppress "bad behavior" from other plugins and themes

= 4.6.2 =
* Added ability to logout automatically locked user
* Updated capability feature to allow set custom capabilities on user level
* Improved Posts & Pages feature for large number of posts
* Few minor bug fixed reported by CodePinch

= 4.6.1 =
* Fixed bug with user capabilities
* Fixed bug with post access settings not being checked even when they are
* Added ability to manage hidden post types
* Added ability to manage number of analyzed posts with get_post_limit config

= 4.6 =
* Fixed internal bug with custom post type LIST control
* Fixed PHP errors in Access Manager metabox
* Fixed bug with customize.php not being able to restrict
* Fixed bug with losing AAM licenses when Clearing all AAM settings
* Fixed bug with not being able to turn off Access Manager metabox rendering
* Fixed bug with access denied default redirect
* Fixed bug with cached javascript library
* Fixed bug with role hierarchy
* Improved media access control
* Improved Double Authentication mechanism
* Improved AAM caching mechanism
* Minor UI improvements
* Added ability to define logout redirect
* Added Access Expiration option to Posts & Pages
* Added ability to turn off post LIST check for performance reasons
* Added ability to add default media image instead of restricted
* Added ability to remove Access link under posts, users title on the list page

= 4.5 =
* Fixed few minor bugs reported by users
* Refactored Extensions functionality
* Added fully functioning Access Manager Widget for both Posts and Categories
* Updated documentation
* Significantly improved performance

= 4.4.1 =
* Adjusted code to support low memory servers

= 4.4 =
* Fixed bug with frontend page redirect
* Significantly improved AAM speed and caching
* Added 404 redirect to the Default Settings

= 4.3.1 =
* Minor bug fixes

= 4.3 =
* Fixed the bug with SSL when WordPress is not configured properly
* Added AAM User Activity extension
* Added ability to track access denied events
* Fixed the bug with internal AAM configurations
* Fixed the bug with login hook when only one argument is passed
* Fixed the bug with invalid argument is passed to password protected check

= 4.2 =
* Fixed the bug with post list caching
* Fixed the bug with Manage Access button
* Added REDIRECT option to post access list
* Added redirect to existing page for Backend tab on Access Denied Redirect
* Improved caching mechanism

= 4.1.1 =
* Fixed bug with Post & Pages UI
* Added ability to define default category for any role or user

= 4.1 =
* Added AAM IP Check extension
* Improved Content filter shortcode to allow other shortcodes inside
* Fixed bug for add/edit role with apostrophe
* Fixed bug with custom Access Denied message
* Fixed bug with data migration

= 4.0.1 =
* Fixed bug with login redirect
* Fixed minor bug with PHP Warnings on Utilities tab
* Fixed post filtering bug
* Updated login shortcode

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

= 3.9.5.1 =
* Fixed bug with login redirect

= 3.9.5 =
* General bug fixing and improvements
* Added ability to setup access settings to all Users, Roles and Visitors
* Added Login Redirect feature

= 3.9.3 =
* Bug fixing
* Implemented license check mechanism
* Improved media access control
* Added ConfigPress extension

= 3.9.2.2 =
* Bug fixing
* Simplified affiliate implementation

= 3.9.2.1 =
* Minor bug fixes reported by CodePinch service

= 3.9.2 =
* Bug fixing
* Internal code improvements
* Extended list of post & pages access options

= 3.9.1.1 =
* Minor bug fix to cover uncommon scenario when user without role

= 3.9.1 =
* Replaced AAM Post Filter extension with core option "Large Post Number Support"
* Removed redundant HTML permalink support
* Visually highlighted editing role or user is administrator
* Hide restricted actions for roles and users on User/Role Panel
* Minor UI improvements
* Significant improvements to post & pages access inheritance mechanism
* Optimized caching mechanism
* Fixed bug with post frontend access

= 3.9 =
* Fixed UI bug with role list
* Fixed core bug with max user level
* Fixed bug with CodePinch installation page
* Added native user switch functionality

= 3.8.3 =
* Fixed the bug with post access inheritance
* Update CodePinch affiliate program

= 3.8.2 =
* Optimized AAM UI to manage large amount of posts and categories
* Improved Multisite support
* Improved UI
* Fixed bug with Extensions tab
* Added ability to check for extension updates manually

= 3.8.1 =
* Minor refactoring
* UI improvements
* Bug fixing

= 3.8 =
* Added Clone Role feature
* Added auto cache clearing on term or post update
* Added init custom URL for metaboxes

= 3.7.6 =
* Fixed bug related to Media Access Control
* Fixed bug with cleaning user posts & pages cache after profile update

= 3.7.5 =
* Added AAM Content Teaser extension
* Added LIMIT option to Posts & Pages access forms to support Teaser feature
* Bug fixing
* Improved UI
* Added ability to show/hide admin bar with show_admin_bar capability

= 3.7.1 =
* Added AAM Role Hierarchy extension
* Fixed bug with 404 page for frontend
* Started CSS fixes for all known incompatible themes and plugins

= 3.7 =
* Introduced Redirect feature
* Added CodePinch widget
* Added AAM Redirect extension
* Added AAM Complete Package extension
* Removed AAM Development extension
* Removed setting Access Denied Handling from the Utilities tab

= 3.6.1 =
* Bug fixing related to URL redirect
* Added back deprecated ConfigPress class to keep compatability with old extensions
* Fixed bug reported through CodePinch service

= 3.6 =
* Added Media Access Control feature
* Added Access Denied Handling feature
* Improved core functionality

= 3.5 =
* Improved access control for Posts & Pages
* Introduced Access Manager metabox to Post edit screen
* Added Access action to list of Posts and Pages
* Improved UI
* Deprecated Skeleton extension in favor to upcoming totally new concept
* Fixed bug with metaboxes initialization when backend filtering is OFF

= 3.4.2 =
* Fixed bug with post & pages access control
* Added Extension version indicator

= 3.4.1 =
* Fixed bug with visitor access control

= 3.4 =
* Refactored backend UI implementation
* Integrated Utilities extension to the core
* Improved capability management functionality
* Improved UI
* Added caching mechanism to the core
* Improved caching mechanism
* Fixed few functional bugs

= 3.3 =
* Improved UI
* Completely protect Admin Menu if restricted
* Tiny core refactoring
* Rewrote UI descriptions

= 3.2.3 =
* Quick fix for extensions ajax calls

= 3.2.2 =
* Improved AAM security reported by James Golovich from Pritect
* Extended core to allow manage access to AAM features via ConfigPress

= 3.2.1 =
* Added show_screen_options capability support to control Screen Options Tab
* Added show_help_tabs capability support to control Help Tabs
* Added AAM Support

= 3.2 =
* Fixed minor bug reporetd by WP Error Fix
* Extended core functionality to support filter by author for Plus Package
* Added Contact Us tab

= 3.1.5 =
* Improved UI
* Fixed the bug reported by WP Error Fix

= 3.1.4 =
* Fixed bug with menu/metabox checkbox
* Added extra hook to clear the user cache after profile update
* Added drill-down button for Posts & Pages tab

= 3.1.3.1 =
* One more minor issue

= 3.1.3 =
* Fixed bug with default post settings
* Filtering roles and capabilities form malicious code

= 3.1.2 =
* Quick fix

= 3.1.1 =
* Fixed potential bug with check user capability functionality
* Added social links to the AAM page

= 3.1 =
* Integrated User Switch with AAM
* Fixed bugs reported by WP Error Fix
* Removed intro message
* Improved AAM speed
* Updated AAM Utilities extension
* Updated AAM Plus Package extension
* Added new AAM Skeleton Extension for developers

= 3.0.10 =
* Fixed bug reported by WP Error Fix when user's first role does not exist
* Fixed bug reported by WP Error Fix when roles has invalid capability set

= 3.0.9 =
* Added ability to extend the AAM Utilities property list
* Updated AAM Plus Package with ability to toggle the page categories feature
* Added WP Error Fix promotion tab
* Finalized and resolved all known issues

= 3.0.8 =
* Extended AAM with few extra core filters and actions
* Added role list sorting by name
* Added WP Error Fix item to the extension list
* Fixed the issue with language file

= 3.0.7 =
* Fixed the warning issue with newly installed AAM instance

= 3.0.6 =
* Fixed issue when server has security policy regarding file_get_content as URL
* Added filters to support Edit/Delete caps with AAM Utilities extension
* Updated AAM Utilities extension
* Refactored extension list manager
* Added AAM Role Filter extension
* Added AAM Post Filter extension
* Standardize the extension folder name

= 3.0.5 =
* Wrapped all *.phtml files into condition to avoid crash on direct file access
* Fixed bug with Visitor subject API
* Added internal capability id to the list of capabilities
* Fixed bug with strict standard notice
* Fixed bug when extension after update still indicates that update is needed
* Fixed bug when extensions were not able to load js & css on windows server
* Updated AAM Utilities extension
* Updated AAM Multisite extension

= 3.0.4 =
* Improved the Metaboxes & Widget filtering on user level
* Improved visual feedback for already installed extensions
* Fixed the bug when posts and categories were filtered on the AAM page
* Significantly improved the posts & pages inheritance mechanism
* Updated and fixed bugs in AAM Plus Package and AAM Utilities
* Improved AAM navigation during page reload
* Removed Trash post access option. Now Delete option is the same
* Added UI feedback on current posts, menu and metaboxes inheritance status
* Updated AAM Multisite extension

= 3.0.3 =
* Fixed bug with backend menu saving
* Fixed bug with metaboxes & widgets saving
* Fixed bug with WP_Filesystem when non-standard filesystem is used
* Optimized Posts & Pages breadcrumb load

= 3.0.2 =
* Fixed a bug with posts access within categories
* Significantly improved the caching mechanism
* Added mandatory notification if caching is not turned on
* Added more help content

= 3.0.1 =
* Fixed the bug with capability saving
* Fixed the bug with capability drop-down menu
* Made backend menu help is more clear
* Added tooltips to some UI buttons

= 3.0 =
* Brand new and much more intuitive user interface
* Fully responsive design
* Better, more reliable and faster core functionality
* Completely new extension handler
* Added "Manage Access" action to the list of user
* Tested against WP 3.8 and PHP 5.2.17 versions

= 2.9.4 =
* Added missing files from the previous commit.

= 2.9.3 =
* Introduced AAM version 3 alpha

= 2.9.2 =
* Small fix in core
* Moved ConfigPress as stand-alone plugin. It is no longer a part of AAM
* Styled the AAM notification message

= 2.8.8 =
* AAM is changing the primary owner to VasylTech
* Removed contextual help menu
* Added notification about AAM v3

= 2.8.7 =
* Tested and verified functionality on the latest WordPress release
* Removed AAM Plus Package. Happy hours are over.

= 2.8.5 =
* Fixed bugs reported by (@TheThree)
* Improved CSS

= 2.8.4 =
* Updated the extension list pricing
* Updated AAM Plugin Manager

= 2.8.3 =
* Improved ConfigPress security (thanks to Tom Adams from security.dxw.com)
* Added ConfigPress new setting control_permalink

= 2.8.2 =
* Fixed issue with Default acces to posts/pages for AAM Plus Package
* Fixed issue with AAM Plugin Manager for lower PHP version

= 2.8.1 =
* Simplified the Repository internal handling
* Added Development License Support

= 2.8 =
* Fixed issue with AAM Control Manage HTML
* Fixed issue with __PHP_Incomplete_Class
* Added AAM Plugin Manager Extension
* Removed Deprecated ConfigPress Object from the core

= 2.7 =
* Fixed bug with subject managing check
* Fixed bug with update hook
* Fixed issue with extension activation hook
* Added AAM Security Feature. First iteration
* Improved CSS

= 2.6 =
* Fixed bug with user inheritance
* Fixed bug with user restore default settings
* Fixed bug with installed extension detection
* Improved core extension handling
* Improved subject inheritance mechanism
* Removed deprecated ConfigPress Tutorial
* Optimized CSS
* Regenerated translation pot file

= 2.5 =
* Fixed issue with AAM Plus Package and Multisite
* Introduced Development License
* Minor internal adjustment for AAM Development Community

= 2.4 =
* Added Norwegian language Norwegian (by Christer Berg Johannesen)
* Localize the default Roles
* Regenerated .pod file
* Added AAM Media Manager Extension
* Added AAM Content Manager Extension
* Standardized Extension Services
* Fixed issue with Media list

= 2.3 =
* Added Persian translation by Ghaem Omidi
* Added Inherit Capabilities From Role drop-down on Add New Role Dialog
* Small Cosmetic CSS changes

= 2.2 =
* Fixed issue with jQuery UI Tooltip Widget
* Added AAM Warning Panel
* Added Event Log Feature
* Moved ConfigPress to separate Page (refactored internal handling)
* Reverted back the SSL handling
* Added Post Delete feature
* Added Post's Restore Default Restrictions feature
* Added ConfigPress Extension turn on/off setting
* Russian translation by (Maxim Kernozhitskiy https://aeromultimedia.com)
* Removed Migration possibility
* Refactored AAM Core Console model
* Increased the number of saved restriction for basic version
* Simplified Undo feature

= 2.1 =
* Fixed issue with Admin Menu restrictions (thanks to MikeB2B)
* Added Polish Translation
* Fixed issue with Widgets restriction
* Improved internal User & Role handling
* Implemented caching mechanism
* Extended Update mechanism (remove the AAM cache after update)
* Added New ConfigPress setting aam.caching (by default is FALSE)
* Improved Metabox & Widgets filtering mechanism
* Added French Translation (by Moskito7)
* Added "My Feature" Tab
* Regenerated .pot file

= 2.0 =
* New UI
* Robust and completely new core functionality
* Over 3 dozen of bug fixed and improvement during 3 alpha & beta versions
* Improved Update mechanism

= 1.0 =
* Fixed issue with comment editing
* Implemented JavaScript error catching