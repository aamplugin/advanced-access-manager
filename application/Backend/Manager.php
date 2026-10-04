<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Manager
{

    /**
     * Single instance of itself
     *
     * @var object
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * Initialize the AAM backend manager
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        AAM_Backend_React::boot();

        // Alter user edit screen with support for multiple roles
        if (AAM::api()->config->get('core.settings.multi_access_levels')) {
            add_action('edit_user_profile', function($user) {
                $this->_update_user_profile_form($user);
            });
            add_action('user_new_form', array($this, 'user_new_form'));

            // User profile update action
            add_action('profile_update', array($this, 'profileUpdate'));
            add_action('user_register', array($this, 'profileUpdate'));
            add_action('added_existing_user', array($this, 'profileUpdate'));
            add_action('wpmu_activate_user', array($this, 'profileUpdate'));
        }

        // Manager Admin Menu
        if (!is_network_admin()) {
            add_action('_user_admin_menu', array($this, 'adminMenu'));
            add_action('_admin_menu', array($this, 'adminMenu'));
        }

        // Manager user search on the AAM page
        add_filter('user_search_columns', function($columns) {
            $columns[] = 'display_name';
            return $columns;
        });

        // Footer thank you
        add_filter('admin_footer_text', array($this, 'thankYou'), 999);

        // Check for pending migration scripts
        if (current_user_can('update_plugins')) {
            // Checking for the new update availability
            $this->_check_for_premium_addon_update();
        }

        add_action( 'admin_enqueue_scripts', function() {
            global $post;

            if (is_a($post, 'WP_Post') && ($post->post_type === 'aam_policy')) {
                $settings = wp_enqueue_code_editor(
                    array('type' => 'application/json')
                );
                $base = plugins_url('media/', AAM_BASEDIR . '/aam.php');
                $style = AAM_BASEDIR . '/media/css/policy-document.css';
                $script = AAM_BASEDIR . '/media/js/policy-document.js';

                wp_enqueue_style('aam-policy-document', $base . 'css/policy-document.css',
                    [], filemtime($style) ?: '1');
                wp_enqueue_style('aam-error-report', $base . 'css/error-report.css',
                    [], filemtime(AAM_BASEDIR . '/media/css/error-report.css') ?: '1');
                wp_enqueue_script('aam-policy-document', $base . 'js/policy-document.js',
                    false !== $settings ? ['code-editor', 'wp-i18n'] : ['wp-i18n'],
                    filemtime($script) ?: '1', true);

                $known = [
                    'abilities' => null,
                    'servers' => null,
                    'tools' => null,
                    'resources' => null,
                    'prompts' => null,
                    'wildcards' => (bool) apply_filters(
                        'aam_ui_policy_wildcards_available_filter', false
                    )
                ];
                if (function_exists('wp_get_abilities')) {
                    $known['abilities'] = array_map(function($ability) {
                        return $ability->get_name();
                    }, wp_get_abilities());
                }
                if (class_exists('\\WP\\MCP\\Core\\McpAdapter')) {
                    $known['servers'] = [];
                    $known['tools'] = [];
                    $known['resources'] = [];
                    $known['prompts'] = [];
                    foreach (\WP\MCP\Core\McpAdapter::instance()->get_servers() as $server) {
                        $id = $server->get_server_id();
                        $schema = null;
                        if (method_exists($server, 'get_schemas')) {
                            $versions = \WP\McpSchema\Schemas::supportedVersions();
                            $schema = $server->get_schemas()->forVersion(end($versions));
                        }
                        $tools = $schema ? $server->get_tools($schema)
                            : $server->get_tools();
                        $resources = $schema ? $server->get_resources($schema)
                            : $server->get_resources();
                        $prompts = $schema ? $server->get_prompts($schema)
                            : $server->get_prompts();
                        $known['servers'][] = $id;
                        foreach ($tools as $tool) {
                            $known['tools'][] = [
                                'server' => $id,
                                'name' => $tool->getName()
                            ];
                        }
                        foreach ($resources as $resource) {
                            $known['resources'][] = [
                                'server' => $id,
                                'name' => $resource->getUri()
                            ];
                        }
                        foreach ($prompts as $prompt) {
                            $known['prompts'][] = [
                                'server' => $id,
                                'name' => $prompt->getName()
                            ];
                        }
                    }
                }
                wp_add_inline_script('aam-policy-document',
                    'window.aamPolicyEditorSettings = ' . wp_json_encode($settings) . ';'
                    . 'window.aamPolicyKnownResources = ' . wp_json_encode($known) . ';',
                    'before');
                wp_set_script_translations('aam-policy-document',
                    'advanced-access-manager', AAM_BASEDIR . '/lang');
            }
        });

        add_filter(
            'network_admin_plugin_action_links_advanced-access-manager/aam.php',
            array($this, 'add_premium_link')
        );
        add_filter(
            'plugin_action_links_advanced-access-manager/aam.php',
            array($this, 'add_premium_link')
        );
    }

    /**
     * Add the premium link
     *
     * @param array $actions
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function add_premium_link($actions)
    {
        if (!defined('AAM_COMPLETE_PACKAGE_LICENSE')) {
            $actions['premium'] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://aamportal.com/premium',
                __('Get Premium', 'advanced-access-manager')
            );
        }

        return $actions;
    }

    /**
     * Check if there is a new premium version available
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _check_for_premium_addon_update()
    {
        $premium = AAM_Addon_Repository::get_instance()->get_premium_data();

        if (!is_null($premium['version']) && $premium['hasUpdate']) {
            AAM_Core_Console::add(__(
                'The new version of premium add-on is available. Go to your license page to download the latest release.',
                'advanced-access-manager'
            ));
        }
    }

    /**
     * Edit existing user page
     *
     * Adding support for the multi-role if this feature is enabled
     *
     * @param WP_User $user
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function _update_user_profile_form($user)
    {
        if (current_user_can('promote_user', $user->ID)) {
            require dirname(__FILE__) . '/tmpl/user/multiple-roles.php';
        }
    }

    /**
     * Adjust user edit/add screen to support multiple roles
     *
     * @param string $param
     *
     * @return void
     * @access public
     * 
     * @version 7.1.2
     */
    public function user_new_form($param)
    {
        require dirname(__FILE__) . '/tmpl/user/multiple-roles.php';
    }

    /**
     * Profile updated hook
     *
     * @param int $id
     *
     * @return void
     *
     * @since 6.6.2 https://github.com/aamplugin/advanced-access-manager/issues/138
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.2
     */
    public function profileUpdate($id)
    {
        $user = get_user_by('ID', $id);

        $is_multirole = AAM::api()->config->get(
            'core.settings.multi_access_levels'
        );

        if ($is_multirole && current_user_can('promote_user', $id)) {
            $roles = filter_input(
                INPUT_POST,
                'aam_user_roles',
                FILTER_DEFAULT,
                FILTER_REQUIRE_ARRAY
            );

            // let's make sure that the list of roles is array
            $roles = (is_array($roles) ? $roles : array());

            // prepare the final list of roles that needs to be set
            $newRoles = array_intersect($roles, array_keys(
                AAM::api()->roles->get_editable_roles(true)
            ));

            if (!empty($newRoles)) {
                // Remove all current roles and then set new
                $user->set_role('');

                foreach ($newRoles as $role) {
                    $user->add_role($role);
                }
            }
        }
    }

    /**
     * Render "Thank You" note on the AAM page
     *
     * @param string $text
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function thankYou($text)
    {
        if ((is_admin() && filter_input(INPUT_GET, 'page') === 'aam')) {
            $text  = '<span id="footer-thankyou">';
            $text .= AAM_Backend_View_Helper::preparePhrase('[Help us] to be more noticeable and submit your review', 'b');
            $text .= ' <a href="https://wordpress.org/support/plugin/advanced-access-manager/reviews/"';
            $text .= 'target="_blank">here</a>';
            $text .= '</span>';
        }

        return $text;
    }

    /**
     * Register AAM Admin Menu
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function adminMenu()
    {
        $bubble = null; // Notification "bubble" for the AAM menu item

        if (current_user_can('aam_show_notifications')) {
            $count = AAM_Core_Console::count();

            if ($count) {
                $bubble = '&nbsp;<span class="update-plugins">'
                    . '<span class="plugin-count">' . $count
                    . '</span></span>';
            }
        }

        $cap_exists = AAM::api()->caps->exists('aam_manager');

        // Register the menu
        add_menu_page(
            'AAM',
            'AAM' . $bubble,
            ($cap_exists ? 'aam_manager' : 'administrator'),
            'aam',
            function() {
                AAM_Backend_React::render();
            },
            file_get_contents(AAM_BASEDIR . '/media/active-menu.data')
        );
    }

    /**
     * Bootstrap the object
     *
     * @return AAM_Backend_Manager
     * @access public
     *
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Backend_Manager
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_instance()
    {
        return self::bootstrap();
    }

}
