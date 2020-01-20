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
 * @since 6.2.2 Added `manage_policies` and removed `blog_id` for the localized
 *              array of properties
 * @since 6.2.0 Added new property to the JS localization `blog_id`
 * @since 6.1.0 Fixed bug with HTML compression
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.2
 */
class AAM_Backend_Manager
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Initialize the AAM backend manager
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        //print required JS & CSS
        add_action('aam_iframe_footer_action', array($this, 'printFooterJavascript'));

        // Alter user edit screen with support for multiple roles
        if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
            add_action('show_user_profile', array($this, 'addMultiRoleSupport'));
            add_action('edit_user_profile', array($this, 'addMultiRoleSupport'));
            add_action('user_new_form', array($this, 'addMultiRoleSupport'));

            // User profile update action
            add_action('profile_update', array($this, 'profileUpdate'), 10, 2);
            add_action('user_register', array($this, 'profileUpdate'), 10, 2);
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
        }
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
     * Adjust user edit/add screen to support multiple roles
     *
     * @param WP_User|string $param
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function addMultiRoleSupport($param)
    {
        require_once dirname(__FILE__) . '/tmpl/user/multiple-roles.php';
    }

    /**
     * Profile updated hook
     *
     * @param int $id
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function profileUpdate($id)
    {
        $user = get_user_by('ID', $id);

        //save selected user roles
        if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
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
                //remove all current roles and then set new
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
     * @access public
     * @version 6.0.0
     */
    public function adminInit()
    {
        $frame = filter_input(INPUT_GET, 'aamframe');

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