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
 * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/212
 * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/179
 * @since 6.6.2 https://github.com/aamplugin/advanced-access-manager/issues/138
 * @since 6.2.2 Added `manage_policies` and removed `blog_id` for the localized
 *              array of properties
 * @since 6.2.0 Added new property to the JS localization `blog_id`
 * @since 6.1.0 Fixed bug with HTML compression
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.8.4
 */
class AAM_Backend_Manager
{

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_SingletonTrait;

    /**
     * Initialize the AAM backend manager
     *
     * @return void
     *
     * @since 6.8.4 https://github.com/aamplugin/advanced-access-manager/issues/212
     * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/179
     * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/88
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.8.4
     */
    protected function __construct()
    {
        // Print required JS & CSS
        add_action('aam_iframe_footer_action', array($this, 'printFooterJavascript'));

        // Alter user edit screen with support for multiple roles
        if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
            add_action('edit_user_profile', array($this, 'editUserProfilePage'));
            add_action('user_new_form', array($this, 'addNewUserPage'));

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

        // Manager AAM Ajax Requests
        add_action('wp_ajax_aam', array($this, 'ajax'));

        // Manager user search on the AAM page
        add_filter('user_search_columns', function($columns) {
            $columns[] = 'display_name';
            return $columns;
        });

        // Footer thank you
        add_filter('admin_footer_text', array($this, 'thankYou'), 999);

        // Control admin area
        add_action('admin_init', array($this, 'adminInit'));

        // Check for pending migration scripts
        if (current_user_can('update_plugins')) {
            $this->checkMigrationStatus();
            $this->checkAddonUpdates();
        }

        add_action( 'admin_enqueue_scripts', function() {
            global $post;

            if (is_a($post, 'WP_Post') && ($post->post_type === 'aam_policy')) {
                $settings = wp_enqueue_code_editor(
                    array('type' => 'application/json')
                );

                if ( false !== $settings ) { // In case Codemirror is disabled
                    wp_add_inline_script(
                        'code-editor',
                        sprintf(
                            'jQuery(() => wp.codeEditor.initialize("%s", %s));',
                            'aam-policy-editor',
                            wp_json_encode($settings)
                        )
                    );
                }
            }
        } );
    }

    /**
     * Check if there are any pending settings and if so, trigger migration
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function checkMigrationStatus()
    {
        if (AAM_Core_Migration::hasPending()) {
            $results = array('errors' => array(), 'dumps' => array());

            foreach(AAM_Core_Migration::getPending() as $filename) {
                $executed = AAM_Core_Migration::executeScript($filename);

                if (!empty($executed['errors'])) {
                    $results['errors'] = array_merge(
                        $results['errors'], $executed['errors']
                    );
                    $results['dumps'][basename($filename)] = $executed['dump'];
                }
            }

            // If there are any errors, store the entire log so user can be notified
            if (!empty($results['errors'])) {
                AAM_Core_Migration::storeFailureLog($results);
            }
        }

        // Check if there are any errors captured during the last migration process
        $log = AAM_Core_Migration::getFailureLog();

        if (!empty($log['errors'])) {
            AAM_Core_Console::add(sprintf(
                __('There was at least one error detected with the automated migration script. %sDownload the log%s for more details and contact our support at %ssupport@aamplugin.com%s for further assistance.', AAM_KEY),
                '<a href="#" id="download-migration-log">', '</a>',
                '<a href="mailto:support@aamplugin.com">', '</a>'
            ));
        }
    }

    /**
     * Display notification if new version is available
     *
     * @return void
     *
     * @access protected
     * @version 6.4.2
     */
    protected function checkAddonUpdates()
    {
        $list = AAM_Addon_Repository::getInstance()->getList();

        foreach($list as $addon) {
            if ($addon['hasUpdate'] === true) {
                AAM_Core_Console::add(
                    sprintf(
                        'The new version of ["%s"] is available for download.',
                        $addon['title']
                    ),
                    'strong'
                );
            }
        }
    }

    /**
     * Print all the necessary JS assets for the AAM UI
     *
     * @return void
     *
     * @since 6.2.2 Added `manage_policies` and removed `blog_id` for the localized
     *              array of properties
     * @since 6.2.0 Added `blog_id` to the localized array of properties
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.2.2
     */
    public function printFooterJavascript()
    {
        if (AAM::isAAM()) {
            $subject  = AAM_Backend_Subject::getInstance();
            $locals   = apply_filters('aam_js_localization_filter', array(
                'nonce'    => wp_create_nonce('aam_ajax'),
                'ajaxurl'  => esc_url(admin_url('admin-ajax.php')),
                'ui'       => AAM_Core_Request::get('aamframe', 'main'),
                'url' => array(
                    'editUser'  => esc_url(admin_url('user-edit.php')),
                    'addUser'   => esc_url(admin_url('user-new.php')),
                    'addPolicy' => esc_url(admin_url('post-new.php?post_type=aam_policy'))
                ),
                'level'     => AAM::getUser()->getMaxLevel(),
                'subject'   => array(
                    'type'  => $subject->getSubjectType(),
                    'id'    => $subject->getId(),
                    'name'  => $subject->getName(),
                    'level' => $subject->getMaxLevel()
                ),
                'system' => array(
                    'apiEndpoint' => AAM_Core_API::getAPIEndpoint()
                ),
                'translation' => AAM_Backend_View_Localization::get(),
                'caps'        => array(
                    'create_roles'    => current_user_can('aam_create_roles'),
                    'create_users'    => current_user_can('create_users'),
                    'manage_policies' => is_main_site()
                )
            ));

            echo '<script type="text/javascript">';
            echo 'var aamLocal = ' . wp_json_encode($locals) . "\n";
            echo file_get_contents(AAM_BASEDIR . '/media/js/vendor.js') . "\n";
            echo file_get_contents(AAM_BASEDIR . '/media/js/aam.js');
            echo '</script>';
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
     *
     * @access public
     * @version 6.7.6
     */
    public function editUserProfilePage($user)
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
     *
     * @access public
     * @version 6.0.0
     */
    public function addNewUserPage($param)
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
     * @since 6.6.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/138
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.2
     */
    public function profileUpdate($id)
    {
        $user = get_user_by('ID', $id);

        $is_multirole = AAM::api()->getConfig('core.settings.multiSubject', false);

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
            $newRoles = array_intersect($roles, array_keys(get_editable_roles()));

            if (!empty($newRoles)) {
                // remove all current roles and then set new
                $user->set_role('');

                foreach ($newRoles as $role) {
                    $user->add_role($role);
                }
            }
        }
    }

    /**
     * Render AAM iframe content if specified
     *
     * @return void
     *
     * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/192
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.9
     */
    public function adminInit()
    {
        $frame = $this->getFromQuery('aamframe');

        if ($frame) {
            echo AAM_Backend_View::getInstance()->renderIFrame($frame);
        }
    }

    /**
     * Render "Thank You" note on the AAM page
     *
     * @param string $text
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function thankYou($text)
    {
        if (AAM::isAAM()) {
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
     *
     * @link https://aamplugin.com/article/how-to-manage-access-to-aam-page-for-other-users
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

        $hasManagerCap = AAM_Core_API::capExists('aam_manager');

        // Register the menu
        add_menu_page(
            'AAM',
            'AAM' . $bubble,
            ($hasManagerCap ? 'aam_manager' : 'administrator'),
            'aam',
            function() {
                echo AAM_Backend_View::getInstance()->renderPage();
            },
            AAM_MEDIA . '/active-menu.svg'
        );
    }

    /**
     * Handle Ajax calls to AAM
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function ajax()
    {
        check_ajax_referer('aam_ajax');

        // Clean buffer to make sure that nothing messing around with system
        while (@ob_end_clean()) { /* Close all the open buffers and flush them */ }

        // Process ajax request
        if (current_user_can('aam_manager')) {
            echo AAM_Backend_View::getInstance()->processAjax();
        } else {
            echo -1;
        }

        exit;
    }

}