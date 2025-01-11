<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Vectorface\Whip\Whip;

/**
 * AAM core service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Core
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * ConfigPress DB option
     *
     * @version 7.0.0
     */
    const CONFIGPRESS_DB_OPTION = 'aam_configpress';

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'core.settings.ui.tips'                  => true,
        'core.settings.multi_access_levels'      => false,
        'core.settings.ui.render_access_metabox' => false,
        'core.settings.xmlrpc_enabled'           => true,
        'core.settings.restful_enabled'          => true,
        'core.settings.merge.preference'         => 'deny',
        'core.export.groups'                     => [ 'settings', 'config', 'roles' ]
    ];

    /**
     * Constructor
     *
     * @access protected
     *
     * @return void
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        // Hook into AAM config initialization and enrich it with ConfigPress
        // settings
        add_filter('aam_initialize_config', function($configs) {
            return $this->_aam_initialize_config($configs);
        });

        if (is_admin()) {
            $metabox_enabled = AAM::api()->config->get(
                'core.settings.ui.render_access_metabox'
            );

            if ($metabox_enabled) {
                add_action('edit_user_profile', function($user) {
                    if (current_user_can('aam_manager')) {
                        $this->_render_access_widget($user);
                    }
                });
            }

            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Settings_Service::register();
                AAM_Backend_Feature_Settings_Core::register();
                AAM_Backend_Feature_Settings_Content::register();
                AAM_Backend_Feature_Settings_ConfigPress::register();
                AAM_Backend_Feature_Settings_Manager::register();
            }, 1);
        }

        // Allow third-party plugins to use AAM user IP detection
        add_filter('aam_get_user_ip_address_filter', function() {
            $whip = new Whip();

            return $whip->getValidIpAddress();
        });

        // Handle access denied
        add_action('aam_deny_access_action', function() {
            AAM::api()->redirect->do_access_denied_redirect();
        });

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
        add_filter('map_meta_cap', function($caps) {
            return $this->_map_meta_cap($caps);
        }, 999);

        // User authentication control
        add_filter('wp_authenticate_user', function($result) {
            return $this->_authenticate_user($result);
        }, 1);

        // Disable XML-RPC if needed
        add_filter('xmlrpc_enabled', function($enabled) {
            if (AAM::api()->config->get(
                'core.settings.xmlrpc_enabled') === false
            ) {
                $enabled = false;
            }

            return $enabled;
        }, PHP_INT_MAX);

        // Disable RESTful API if needed
        add_filter('rest_authentication_errors', function ($response) {
            if (!current_user_can('aam_manager')
                && !is_wp_error($response)
                && !AAM::api()->config->get('core.settings.restful_enabled')
            ) {
                $response = new WP_Error(
                    'rest_access_disabled',
                    __('RESTful API is disabled', AAM_KEY),
                    array('status' => 403)
                );
            }

            return $response;
        }, PHP_INT_MAX);

        // Control user's status
        add_action('set_current_user', function() {
            $this->_control_user_account();
        });

        // Run upgrades if available
        // AAM_Core_Migration::run();

        // Bootstrap RESTful API
        AAM_Restful_MuService::bootstrap();
    }

    /**
     * Initialize configurations by adding ConfigPress settings
     *
     * @param array $configs
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _aam_initialize_config($configs)
    {
        $configpress = AAM::api()->db->read(self::CONFIGPRESS_DB_OPTION);

        if (!empty($configpress) && is_string($configpress)) {
            $result = parse_ini_string($configpress, true, INI_SCANNER_TYPED);

            if ($result !== false) { // Clear error
                // If we have "aam" key, then AAM ConfigPress is properly formatted
                // and we take all the values from this section.
                // Otherwise - assume that user forgot to add the "[aam]" section
                if (array_key_exists('aam', $result)) {
                    $configs = array_merge($configs, $result['aam']);
                } else {
                    $configs = array_merge($configs, $result);
                }
            }
        }

        return $configs;
    }

    /**
     * Render "Access Manager" widget on the user/profile edit screen
     *
     * @param WP_User $user
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    private function _render_access_widget($user)
    {
        echo AAM_Backend_View::get_instance()->renderUserMetabox($user);
    }

    /**
     * Check user capability
     *
     * This is a hack function that add additional layout on top of WordPress
     * core functionality. Based on the capability passed in the $args array as
     * "0" element, it performs additional check on user's capability to manage
     * post, users etc.
     *
     * @param array $caps
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    private function _map_meta_cap($caps)
    {
        // Mutate any AAM specific capability if it does not exist
        foreach ((array) $caps as $i => $capability) {
            if (
                is_string($capability) && (strpos($capability, 'aam_') === 0)
                && !AAM::api()->capabilities->exists($capability)
            ) {
                $caps[$i] = AAM::api()->config->get(
                    'page.capability',
                    'administrator'
                );
            }
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
     * @access public
     *
     * @version 7.0.0
     */
    private function _authenticate_user($result)
    {
        // Check if user is blocked
        if (is_a($result, 'WP_User')) {
            $user = AAM::api()->users->get_user($result);

            // Step #1. Verify that user is active
            if (!$user->is_user_active()) {
                $result = new WP_Error(
                    'inactive_user',
                    '[ERROR]: User is inactive. Contact the administrator.'
                );
            }

            // Step #2. Verify that user is not expired
            if ($user->is_user_access_expired()) {
                $result = new WP_Error(
                    'inactive_user',
                    '[ERROR]: User access is expired. Contact the administrator.'
                );
            }
        }

        return $result;
    }

    /**
     * Verify user status and act accordingly if user is no longer active or
     * expired
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    private function _control_user_account()
    {
        $user = AAM::api()->users->get_user(get_current_user_id());

        if (!is_null($user)) {
            // If user status is inactive - immediately logout user
            if ($user->is_user_active() === false) {
                wp_logout();
                exit;
            } elseif ($user->is_user_access_expired()) {
                // Trigger specified action
                switch ($user->expiration_trigger['type']) {
                    case 'change-role':
                    case 'change_role':
                        $user->set_role($user->expiration_trigger['to_role']);
                        $user->reset('expiration');
                        break;

                    case 'delete': // For compatibility purposes
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

}