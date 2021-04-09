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
 * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/126
 * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/82
 * @since 6.4.0 Added "Manage Access" toolbar item to single & multisite network
 * @since 6.0.5 Making sure that only if user is allowed to manage other users
 * @since 6.0.4 Bug fixing. Unwanted "Access Denied" metabox on the Your Profile page
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.5
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
     * Constructor
     *
     * @access protected
     *
     * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/82
     * @since 6.4.0 Added "Manage Access" toolbar item
     * @since 6.0.5 Fixed bug when Access Manager metabox is rendered for users that
     *              have ability to manage other users
     * @since 6.0.4 Fixed bug when Access Manager metabox is rendered on profile edit
     *              page
     * @since 6.0.0 Initial implementation of the method
     *
     * @return void
     * @version 6.4.2
     */
    protected function __construct()
    {
        if (AAM_Addon_Repository::getInstance()->hasRegistry()) {
            // Plugin updates check
            add_filter('http_response', array($this, 'checkForUpdates'), 10, 3);

            // Plugin release details
            add_filter('self_admin_url', array($this, 'pluginUpdateDetails'), 10);
        }

        if (is_admin()) {
            $metaboxEnabled = AAM_Core_Config::get(
                'ui.settings.renderAccessMetabox', true
            );

            if ($metaboxEnabled && current_user_can('aam_manager')) {
                add_action('edit_user_profile', array($this, 'renderAccessWidget'));
            }

            // Hook that initialize the AAM UI part of the service
            add_action('aam_init_ui_action', function () {
                AAM_Backend_Feature_Subject_Role::register();
                AAM_Backend_Feature_Subject_User::register();

                AAM_Backend_Feature_Settings_Service::register();
                AAM_Backend_Feature_Settings_Core::register();
                AAM_Backend_Feature_Settings_Content::register();
                AAM_Backend_Feature_Settings_ConfigPress::register();
                AAM_Backend_Feature_Settings_Manager::register();
                AAM_Backend_Feature_Addons_Manager::register();
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
                $blog_count = count($wp_admin_bar->user->blogs);

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
        add_filter('map_meta_cap', array($this, 'mapMetaCaps'), 999, 4);

        // Security. Make sure that we escaping all translation strings
        add_filter('gettext', array($this, 'escapeTranslation'), 999, 3);

        // User expiration hook
        add_action('aam_set_user_expiration_action', function($settings) {
            AAM::getUser()->setUserExpiration($settings);
        });

        // If there are any license violations. Display the notification for users
        // that have enough permissions to manage AAM
        if (is_admin() && current_user_can('aam_manager')) {
            if (AAM_Addon_Repository::getInstance()->hasViolations()) {
                if (!AAM::isAAM()) {
                    add_action('admin_notices', function() {
                        require __DIR__ . '/../Backend/tmpl/partial/license-violation-notice.php';
                    });
                }

                // Also add all the identified violations to the AAM console
                foreach(AAM_Addon_Repository::getInstance()->getViolations() as $v) {
                    AAM_Core_Console::add($v);
                }
            }
        }
    }

    /**
     * Check for premium plugin updates
     *
     * @param array  $response
     * @param string $r
     * @param string $url
     *
     * @return array
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/126
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.5
     */
    public function checkForUpdates($response, $r, $url)
    {
        if (strpos($url, self::PLUGIN_CHECK_URI) !== false) {
            $repository = AAM_Addon_Repository::getInstance();

            // Fetch registry from the AAM server
            $raw = wp_remote_post(
                AAM_Core_API::getAPIEndpoint() . '/registry',
                array(
                    'headers' => array(
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json'
                    ),
                    'body'    => wp_json_encode($repository->getAddonLicenseMap())
                )
            );

            // Making sure that we are getting successful response
            $is_error = is_wp_error($raw);
            $is_ok    = intval(wp_remote_retrieve_response_code($raw)) === 200;

            if (!$is_error && $is_ok) {
                $original = json_decode($response['body'], true);
                $new_body = json_decode(wp_remote_retrieve_body($raw), true);

                // Looping through each product in the response and comparing to the
                // currently installed version on the website and if match found,
                // override the original response to indicate that new version is
                // available
                if (!empty($new_body['products']) && is_array($new_body['products'])) {
                    foreach ($new_body['products'] as $item) {
                        $v     = $repository->getPluginVersion($item['plugin']);
                        $new_v = $item['new_version'];

                        if (!empty($v) && (version_compare($v, $new_v) === -1)) {
                            $original['plugins'][$item['plugin']] = $item;
                        }

                        if (!empty($item['violation'])) {
                            $repository->processViolation(
                                $item['slug'],
                                $item['violation'],
                                (isset($item['action']) ? $item['action'] : null)
                            );
                        }
                    }
                }

                $response['body'] = json_encode($original);
            }
        }

        return $response;
    }

    /**
     * Generate plugin update/changelog details URL
     *
     * @param string $url
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function pluginUpdateDetails($url)
    {
        if (strpos($url, 'plugin-install.php?') !== false) {
            $args  = parse_url($url);
            $query = array();

            parse_str($args['query'], $query);

            $plugin  = !empty($query['plugin']) ? $query['plugin'] : null;
            $aam_ids = array_keys(AAM_Addon_Repository::getInstance()->getList());

            if (in_array($plugin, $aam_ids)) {
                $url = add_query_arg(array(
                    'TB_iframe' => true,
                    'width'     => (isset($query['width']) ? $query['width'] : 640),
                    'height'    => (isset($query['height']) ? $query['height'] : 662),
                ), 'https://aamplugin.com/addon/' . $plugin . '/changelog');
            }
        }

        return $url;
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
     * Eliminate XSS through translation file
     *
     * @param string $translation
     * @param string $text
     * @param string $domain
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function escapeTranslation($translation, $text, $domain)
    {
        if ($domain === AAM_KEY) {
            $translation = esc_js($translation);
        }

        return $translation;
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
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/126
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.3
     */
    public function mapMetaCaps($caps, $cap, $user_id, $args)
    {
        $objectId = (isset($args[0]) ? $args[0] : null);

        // Mutate any AAM specific capability if it does not exist
        foreach ((array) $caps as $i => $capability) {
            if (
                (strpos($capability, 'aam_') === 0)
                && !AAM_Core_API::capExists($capability)
            ) {
                $caps[$i] = AAM_Core_Config::get(
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
        $allow = apply_filters('aam_allowed_plugin_action_filter', null, $action);

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