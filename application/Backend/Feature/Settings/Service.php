<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM services
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_Service extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_services';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'settings/service.php';

    /**
     * Get list of services
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function getList()
    {
        $response = apply_filters('aam_service_list_filter', [
            [
                'title'       => __('Access Denied Redirect', 'advanced-access-manager'),
                'description' => __('Manage the default access-denied redirect separately for the frontend and backend when access to any protected website resource is denied.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_AccessDeniedRedirect::class]
            ],
            [
                'title'       => __('Admin Toolbar', 'advanced-access-manager'),
                'description' => __('Manage access to the top admin toolbar items for any role or individual user. The service only removes restricted items but does not actually protect from direct access via link.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_AdminToolbar::class]
            ],
            [
                'title'       => __('API Routes', 'advanced-access-manager'),
                'description' => __('Manage access to any individual RESTful endpoint for any role, user or unauthenticated application request. The service works great with JWT service that authenticate requests with JWT Bearer token.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_ApiRoute::class]
            ],
            [
                'title'       => __('Backend Menu', 'advanced-access-manager'),
                'description' => __('Manage access to the admin (backend) main menu for any role or individual user. The service removes restricted menu items and protects direct access to them.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_BackendMenu::class]
            ],
            [
                'title'       => __('Capabilities', 'advanced-access-manager'),
                'description' => __('Manage list of all the registered with WordPress core capabilities for any role or individual user. The service allows to create new or update and delete existing capabilities. Very powerful set of tools for more advanced user/role access management.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Capability::class]
            ],
            [
                'title'       => __('Hooks', 'advanced-access-manager'),
                'description' => __('Take control of your WordPress hooks (actions and filters). Manage their execution and customize return values with precision.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Hooks::class]
            ],
            [
                'title'       => __('Posts & Terms', 'advanced-access-manager'),
                'description' => __('Manage access to your website content for any user, role or visitor. This include access to posts, pages, media attachment, custom post types, categories, tags, custom taxonomies and terms.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Content::class]
            ],
            [
                'title'       => __('Identity Governance', 'advanced-access-manager'),
                'description' => __('Control how other users and unauthenticated visitors can view and manage the profiles of registered users on the site.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Identity::class]
            ],
            [
                'title'       => __('JWT Tokens', 'advanced-access-manager'),
                'description' => __('Manage the website authentication with JWT Bearer token. The service facilitates the ability to manage the list of issued JWT token for any user, revoke them or issue new on demand.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Jwt::class]
            ],
            [
                'title'       => __('Login Redirect', 'advanced-access-manager'),
                'description' => __('Handle login redirects for any user group or individual user upon successful authentication.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_LoginRedirect::class]
            ],
            [
                'title'       => __('Logout Redirect', 'advanced-access-manager'),
                'description' => __('Manage the logout redirect for any group of users or individual users after they have successfully logged out.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_LogoutRedirect::class]
            ],
            [
                'title'       => __('Metaboxes', 'advanced-access-manager'),
                'description' => __('Control the visibility of classic backend metaboxes for any role, user, or visitor. This service exclusively hides unwanted metaboxes from the admin screens.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Metaboxes::class]
            ],
            [
                'title'       => __('404 Redirect', 'advanced-access-manager'),
                'description' => __('Handle frontend 404 (Not Found) redirects for any group of users or individual user.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_NotFoundRedirect::class]
            ],
            [
                'title'       => __('Shortcodes', 'advanced-access-manager'),
                'description' => __('Classic WordPress shortcodes that allow to manage access to parts of a frontend content as well as some UI helpers.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Shortcodes::class]
            ],
            [
                'title'       => __('Access Policies', 'advanced-access-manager'),
                'description' => __('Control website access using thoroughly documented JSON policies for users, roles, and visitors. Maintain a detailed record of all access changes and policy revisions.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Policies::class]
            ],
            [
                'title'       => __('Secure Login', 'advanced-access-manager'),
                'description' => __('Enhance default WordPress authentication process with more secure login mechanism. The service registers frontend AJAX Login widget as well as additional endpoints for the RESTful API authentication.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_SecureLogin::class]
            ],
            [
                'title'       => __('Security Scan', 'advanced-access-manager'),
                'description' => __('This automated security scan service conducts a series of checks to verify the integrity of your website\'s configurations and detect any potential elevated privileges for users and roles.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_SecurityAudit::class]
            ],
            [
                'title'       => __('URL Access', 'advanced-access-manager'),
                'description' => __('Manage direct access to website URLs for any role or individual user. Define specific URLs or use wildcards (with the premium add-on). Control user requests by setting rules to allow, deny, or redirect access.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Urls::class]
            ],
            [
                'title'       => __('Welcome', 'advanced-access-manager'),
                'description' => __('This service provides a simple overview of the plugin and its capabilities. It presents essential information about how AAM can enhance your experience and streamline your tasks. Explore the features and benefits of AAM and discover how it can help you achieve your goals efficiently.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Welcome::class]
            ],
            [
                'title'       => __('Widgets', 'advanced-access-manager'),
                'description' => __('Control the visibility of widgets on the backend and frontend for any role, user, or visitor. This service exclusively hides unwanted widgets.', 'advanced-access-manager'),
                'setting'     => AAM::SERVICES[AAM_Service_Widgets::class]
            ]
        ]);

        // Get each service status
        foreach ($response as &$item) {
            if (!array_key_exists('status', $item)) {
                $item['status'] = AAM::api()->config->get($item['setting'], true);
            }
        }

        return $response;
    }

    /**
     * Register services settings tab
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'settings-services',
            'position'   => 1,
            'title'      => __('Services', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}