<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core service
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/390
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/276
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/268
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/265
 * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/243
 * @since 6.9.3  https://github.com/aamplugin/advanced-access-manager/issues/236
 * @since 6.7.5  https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.5.3  https://github.com/aamplugin/advanced-access-manager/issues/126
 * @since 6.4.2  https://github.com/aamplugin/advanced-access-manager/issues/82
 * @since 6.4.0  Added "Manage Access" toolbar item to single & multi-site network
 * @since 6.0.5  Making sure that only if user is allowed to manage other users
 * @since 6.0.4  Bug fixing. Unwanted "Access Denied" metabox on the Your Profile page
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Service_Core
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * URI that is used to check for plugin updates
     *
     * @version 6.0.0
     */
    const PLUGIN_CHECK_URI = 'api.wordpress.org/plugins/update-check';

    /**
     * Default configurations
     *
     * @version 6.9.34
     */
    const DEFAULT_CONFIG = [
        'core.settings.tips'              => true,
        'core.settings.multiSubject'      => false,
        'ui.settings.renderAccessMetabox' => false,
        'core.settings.merge.preference'  => 'deny',
        'core.export.groups'              => [ 'settings', 'config', 'roles' ]
    ];

    /**
     * Constructor
     *
     * @access protected
     *
     * @since 6.9.32 https://github.com/aamplugin/advanced-access-manager/issues/390
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/276
     * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/268
     * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/243
     * @since 6.9.3  https://github.com/aamplugin/advanced-access-manager/issues/236
     * @since 6.4.2  https://github.com/aamplugin/advanced-access-manager/issues/82
     * @since 6.4.0  Added "Manage Access" toolbar item
     * @since 6.0.5  Fixed bug when Access Manager metabox is rendered for users that
     *               have ability to manage other users
     * @since 6.0.4  Fixed bug when Access Manager metabox is rendered on profile edit
     *               page
     * @since 6.0.0  Initial implementation of the method
     *
     * @return void
     * @version 6.9.32
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        if (is_admin()) {
            $metaboxEnabled = AAM_Framework_Manager::configs()->get_config(
                'ui.settings.renderAccessMetabox'
            );

            if ($metaboxEnabled && current_user_can('aam_manager')) {
                add_action('edit_user_profile', array($this, 'renderAccessWidget'));
            }

            // Hook that initialize the AAM UI part of the service
            add_action('aam_init_ui_action', function () {
                AAM_Backend_Feature_Settings_Service::register();
                AAM_Backend_Feature_Settings_Core::register();
                AAM_Backend_Feature_Settings_Content::register();
                AAM_Backend_Feature_Settings_ConfigPress::register();
                AAM_Backend_Feature_Settings_Manager::register();
            }, 1);
        }

        // Add toolbar "Manage Access" item
        add_action('admin_bar_menu', function($wp_admin_bar) {
            if (current_user_can('aam_manager')) {
                $wp_admin_bar->add_menu(
                    array(
                        'parent' => 'site-name',
                        'id'     => 'aam',
                        'title'  => __('Manager Access', AAM_KEY),
                        'href'   => admin_url('admin.php?page=aam'),
                    )
                );
            }
        }, 999);

        // Add "Manage Access" to all sites if multisite network
        if (is_multisite()) {
            add_action('admin_bar_menu', function($wp_admin_bar) {
                $blog_count = 0;

                if (is_a($wp_admin_bar->user, 'stdClass')) {
                    if (is_iterable($wp_admin_bar->user->blogs)) {
                        $blog_count = count($wp_admin_bar->user->blogs);
                    }
                }

                if ($blog_count > 0 || current_user_can('manage_network')) {
                    foreach((array) $wp_admin_bar->user->blogs as $blog) {
                        switch_to_blog($blog->userblog_id);

                        $menu_id = 'blog-' . $blog->userblog_id;

                        if (current_user_can('aam_manager')) {
                            $wp_admin_bar->add_menu(
                                array(
                                    'parent' => $menu_id,
                                    'id'     => $menu_id . '-aam',
                                    'title'  => __('Manage Access', AAM_KEY),
                                    'href'   => admin_url('admin.php?page=aam'),
                                )
                            );
                        }

                        restore_current_blog();
                    }
                }
            }, 999);
        }

        // Check if user has ability to perform certain task based on provided
        // capability and meta data
        add_filter('map_meta_cap', array($this, 'map_meta_cap'), 999, 4);

        // User authentication control
        add_filter(
            'wp_authenticate_user', array($this, 'authenticate_user'), 1, 2
        );

        // Run upgrades if available
        AAM_Core_Migration::run();

        // Bootstrap RESTful API
        AAM_Restful_MuService::bootstrap();
    }

    /**
     * Render "Access Manager" widget on the user/profile edit screen
     *
     * @param WP_User $user
     *
     * @return void
     *
     * @since 6.0.5 Making sure that user metabox is rendered only if user is allowed
     *              to manage other users
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.0.5
     */
    public function renderAccessWidget($user)
    {
        if (current_user_can('aam_manage_users')) {
            echo AAM_Backend_View::getInstance()->renderUserMetabox($user);
        }
    }

    /**
     * Check user capability
     *
     * This is a hack function that add additional layout on top of WordPress
     * core functionality. Based on the capability passed in the $args array as
     * "0" element, it performs additional check on user's capability to manage
     * post, users etc.
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param array  $args
     *
     * @return array
     *
     * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/265
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/126
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     */
    public function map_meta_cap($caps, $cap, $user_id, $args)
    {
        $objectId = (isset($args[0]) ? $args[0] : null);

        // Mutate any AAM specific capability if it does not exist
        foreach ((array) $caps as $i => $capability) {
            if (
                is_string($capability) && (strpos($capability, 'aam_') === 0)
                && !AAM_Core_API::capExists($capability)
            ) {
                $caps[$i] = AAM_Framework_Manager::configs()->get_config(
                    'page.capability',
                    'administrator'
                );
            }
        }

        switch ($cap) {
            case 'install_plugins':
            case 'delete_plugins':
            case 'edit_plugins':
            case 'update_plugins':
                $action = explode('_', $cap);
                $caps   = $this->checkPluginsAction($action[0], $caps, $cap);
                break;

            case 'activate_plugin':
            case 'deactivate_plugin':
                $action = explode('_', $cap);
                $caps   = $this->checkPluginAction(
                    $objectId, $action[0], $caps, $cap
                );
                break;

            default:
                break;
        }

        return $caps;
    }

    /**
     * Validate user status
     *
     * Check if user is locked or not
     *
     * @param WP_Error $user
     *
     * @return WP_Error|WP_User
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/276
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.10
     */
    public function authenticate_user($user)
    {
        // Check if user is blocked
        if (is_a($user, 'WP_User')) {
            $result = AAM_Framework_Manager::users([
                'error_handling' => 'wp_error'
            ])->verify_user_state($user);

            if (is_wp_error($result)) {
                $user = $result;
            }
        }

        return $user;
    }

    /**
     * Verify user status and act accordingly if user is no longer active or
     * expired
     *
     * @return void
     *
     * @access public
     * @version 6.9.33
     */
    public function verify_user_status()
    {
        if (is_user_logged_in()) {
            $user = AAM_Framework_Manager::users()->get_user(
                get_current_user_id()
            );

            // If user status is inactive - immediately logout user
            if ($user->is_user_active() === false) {
                wp_logout();
                exit;
            } elseif ($user->is_user_access_expired()) {
                // Trigger specified action
                switch ($user->expiration_trigger['type']) {
                    case 'change-role':
                    case 'change_role':
                        $user->set_role(
                            $user->expiration_trigger['type']
                        );
                        $user->reset('expiration');
                        break;

                    case 'delete':
                        require_once(ABSPATH . 'wp-admin/includes/user.php');

                        wp_delete_user(
                            $user->ID,
                            AAM_Framework_Manager::configs()->get_config(
                                'core.reasign.ownership.user'
                            )
                        );
                        break;

                    case 'lock':
                        $user->update(['status' => $user::STATUS_INACTIVE]);
                        $user->reset('expiration');
                        // And logout immediately

                    case 'logout':
                        wp_logout();
                        exit;
                        break;

                    default:
                        break;
                }
            }
        }
    }

    /**
     * Check if specific action for plugins is allowed
     *
     * @param string $action
     * @param array  $caps
     * @param string $cap
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function checkPluginsAction($action, $caps, $cap)
    {
        $allow = apply_filters(
            'aam_allowed_plugin_action_filter', null, $action
        );

        if ($allow !== null) {
            $caps[] = $allow ? $cap : 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Check if specific action is allowed upon provided plugin
     *
     * @param string $plugin
     * @param string $action
     * @param array  $caps
     * @param string $cap
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function checkPluginAction($plugin, $action, $caps, $cap)
    {
        $parts = explode('/', $plugin);
        $slug  = (!empty($parts[0]) ? $parts[0] : null);

        $allow = apply_filters(
            'aam_allowed_plugin_action_filter', null, $action, $slug
        );

        if ($allow !== null) {
            $caps[] = $allow ? $cap : 'do_not_allow';
        }

        return $caps;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Core::bootstrap();
}