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
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Manager {

    /**
     * Single instance of itself
     * 
     * @var AAM_Backend_Manager
     * 
     * @access private 
     */
    private static $_instance = null;
    
    /**
     * Initialize the object
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //check if user is allowed to see backend
        $this->checkUserAccess();
        
        //check if user switch is required
        $this->checkUserSwitch();
        
        //cache clearing hook
        add_action('aam-clear-cache-action', 'AAM_Core_API::clearCache');
        
        //print required JS & CSS
        add_action('admin_print_scripts', array($this, 'printJavascript'));
        add_action('admin_print_footer_scripts', array($this, 'printFooterJavascript'));
        add_action('admin_print_styles', array($this, 'printStylesheet'));
        
        //user profile update action
        add_action('profile_update', array($this, 'profileUpdate'), 10, 2);

        //alter user edit screen with support for multiple roles
        if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
            add_action('show_user_profile', array($this, 'userEditPage'));
            add_action('edit_user_profile', array($this, 'userEditPage'));
        }
        
        //post title decorator
        add_filter('the_title', array($this, 'theTitle'), 999, 2);
        
        //permalink manager
        add_filter('get_sample_permalink_html', array($this, 'getPermalinkHtml'), 10, 5);
        
        //access policy save
        add_filter('wp_insert_post_data', array($this, 'filterPostData'), 10, 2);
        
        //screen options & contextual help hooks
        add_filter('screen_options_show_screen', array($this, 'screenOptions'));
        add_filter('contextual_help', array($this, 'helpOptions'), 10, 3);
        
        //manager Admin Menu
        if (is_multisite() && is_network_admin()) {
            //register AAM in the network admin panel
            add_action('_network_admin_menu', array($this, 'adminMenu'));
        } else {
            add_action('_user_admin_menu', array($this, 'adminMenu'));
            add_action('_admin_menu', array($this, 'adminMenu'));
            add_action('all_admin_notices', array($this, 'notification'));
        }
        
        if (AAM_Core_Config::get('ui.settings.renderAccessMetabox', true)) {
            add_action('edit_category_form_fields', array($this, 'renderTermMetabox'), 1);
            add_action('edit_link_category_form_fields', array($this, 'renderTermMetabox'), 1);
            add_action('edit_tag_form_fields', array($this, 'renderTermMetabox'), 1);
            
            //register custom access control metabox
            add_action('add_meta_boxes', array($this, 'metabox'));
        }
        
        //register custom access control metabox
        add_action('add_meta_boxes', array($this, 'registerPolicyDocMetabox'));
        
        //manager AAM Ajax Requests
        add_action('wp_ajax_aam', array($this, 'ajax'));
        //manager AAM Features Content rendering
        add_action('admin_action_aamc', array($this, 'renderContent'));
        //manager user search and authentication control
        add_filter('user_search_columns', array($this, 'searchColumns'));
        
        //manager WordPress metaboxes
        add_action("in_admin_header", array($this, 'initMetaboxes'), 999);
        
        // manage Navigation Menu page to support
        // https://forum.aamplugin.com/d/61-restrict-role-from-updating-or-deleting-specific-navigation-menus
        add_filter('nav_menu_meta_box_object', array($this, 'manageNavMenuMetabox'));
        
        if (AAM_Core_Config::get('ui.settings.renderAccessActionLink', true)) {
            //extend post inline actions
            add_filter('page_row_actions', array($this, 'postRowActions'), 10, 2);
            add_filter('post_row_actions', array($this, 'postRowActions'), 10, 2);

            //extend term inline actions
            add_filter('tag_row_actions', array($this, 'tagRowActions'), 10, 2);
            
            //manage access action to the user list
            add_filter('user_row_actions', array($this, 'userActions'), 10, 2);
        }
        
        //footer thank you
        add_filter('admin_footer_text', array($this, 'thankYou'), 999);
        
        //control admin area
        add_action('admin_init', array($this, 'adminInit'));
        
        //password reset feature
        add_filter('show_password_fields', array($this, 'canChangePassword'), 10, 2);
        add_action('check_passwords', array($this, 'canUpdatePassword'), 10, 3);
        
        //admin toolbar
        if (AAM::isAAM()) {
            add_action('wp_after_admin_bar_render', array($this, 'cacheAdminBar'));
        }
        
        //register login widget
        if (AAM_Core_Config::get('core.settings.secureLogin', true)) {
            add_action('widgets_init', array($this, 'registerLoginWidget'));
            add_action('wp_ajax_nopriv_aamlogin', array($this, 'handleLogin'));
        }
        
        //register backend hooks and filters
        if (AAM_Core_Config::get('core.settings.backendAccessControl', true)) {
            AAM_Backend_Filter::register();
        }
        
        AAM_Extension_Repository::getInstance()->hasUpdates();
        
        if (version_compare(PHP_VERSION, '5.3.0') === -1) {
            AAM_Core_Console::add(
                'AAM requires PHP version 5.3.0 or higher to function properly'
            );
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $user
     * @return void
     */
    public function userEditPage($user) {
        ob_start();
        require_once dirname(__FILE__) . '/phtml/user/multiple-roles.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        echo $content;
    }
    
    /**
     * 
     * @param type $postType
     * @return type
     */
    public function manageNavMenuMetabox($postType) {
        $postType->_default_query['suppress_filters'] = false;
        
        return $postType;
    }
    
    /**
     * 
     * @param boolean $result
     * @param type $user
     * @return boolean
     */
    public function canChangePassword($result, $user) {
        $isProfile = $user->ID === get_current_user_id();
        if ($isProfile) {
            if (AAM_Core_API::capabilityExists('change_own_password') 
                && !current_user_can('change_own_password')) {
                $result = false;
            }
        } elseif (AAM_Core_API::capabilityExists('change_passwords') 
                && !current_user_can('change_passwords')) {
            $result = false;
        }
        
        return $result;
    }
    
    /**
     * 
     * @param type $login
     * @param type $password
     */
    public function canUpdatePassword($login, &$password, &$password2) {
        $userId    = AAM_Core_Request::post('user_id');
        $isProfile = $userId === get_current_user_id();
        
        if ($isProfile) {
            if (AAM_Core_API::capabilityExists('change_own_password') 
                && !current_user_can('change_own_password')) {
                $password = $password2 = null;
            }
        } elseif (AAM_Core_API::capabilityExists('change_passwords') 
                && !current_user_can('change_passwords')) {
            $password = $password2 = null;
        }
    }
    
    /**
     * 
     * @param type $data
     * @return type
     */
    public function filterPostData($data) {
        if (isset($data['post_type']) && ($data['post_type'] === 'aam_policy')) {
            $content = trim(filter_input(INPUT_POST, 'aam-policy'));
            
            if (!empty($content)) { // Edit form was submitted
                $data['post_content'] = addslashes($content);
            }
            
            if (empty($data['post_content'])) {
                $data['post_content'] = AAM_Backend_View_Helper::getDefaultPolicy();
            }
            
            AAM_Core_API::clearCache();
        }
        
        return $data;
    }
    
    /**
     * 
     */
    public function renderExportFields() {
        ob_start();
        require_once dirname(__FILE__) . '/phtml/system/export.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        echo $content;
    }
    
    /**
     * 
     * @param type $args
     * @return type
     */
    public function prepareExportArgs($args) {
        if ($args['content'] === 'aam') {
            $export = array();
            
            foreach(AAM_Core_Request::get('export', array()) as $group => $settings) {
                $export[$group] = implode(',', $settings);
            }
            
            if (empty($export)) {
                $export = array('system' => 'roles,utilities,configpress');
            }
            
            $args['export'] = $export;
        }
        
        return $args;
    }
    
    /**
     * 
     * @param type $args
     */
    public function exportSettings($args) {
        if ($args['content'] === 'aam') {
            $filename = 'aam.export.' . date('Y-m-d') . '.json';
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
            $exporter = new AAM_Core_Exporter($args['export']);
            echo wp_json_encode($exporter->run());
            die();
        }
    }
    
    /**
     * 
     */
    protected function registerAAMImporter() {
        register_importer(
            'aam', 
            'AAM Access Settings', 
            'Advanced Access Manager access settings and configurations', 
            array($this, 'renderImporter')
        );
    }
    
    /**
     * 
     */
    public function renderImporter() {
        $importer = new AAM_Core_Importer();
        $importer->dispatch();
    }
    
    /**
     * 
     * @param string $html
     * @return string
     */
    public function getPermalinkHtml($html) {
        if (AAM_Core_API::capabilityExists('edit_permalink') 
                && !current_user_can('edit_permalink')) {
            $html = '';
        }
        
        return $html;
    }
    
    /**
     * Profile updated hook
     * 
     * Adjust expiration time and user cache if profile updated
     * 
     * @param int     $id
     * @param WP_User $old
     * 
     * @return void
     * 
     * @access public
     */
    public function profileUpdate($id, $old) {
        $user = get_user_by('ID', $id);

        //save selected user roles
        if (AAM::api()->getConfig('core.settings.multiSubject', false)) {
            $roles = filter_input(INPUT_POST, 'aam_user_roles', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);

            // prepare the final list of roles that needs to be set
            $newRoles = array_intersect($roles, array_keys(get_editable_roles()));

            if (!empty($newRoles)) {
                //remove all current roles and then set new
                $user->set_role('');
                // TODO: Fix the bug where multiple roles are not removed 
                foreach($newRoles as $role) {
                    $user->add_role($role);
                }
            }
        }
        
        //role changed?
        if (implode('', $user->roles) !== implode('', $old->roles)) {
            AAM_Core_API::clearCache(new AAM_Core_Subject_User($id));
        }
    }
    
    /**
     * Filter post title
     * 
     * @param string $title
     * @param int    $id
     * 
     * @return string
     * 
     * @access public
     */
    public function theTitle($title, $id = null) {
        if (empty($title) && AAM::isAAM()) { //apply filter only for AAM page
            $title = '[No Title]: ID ' . ($id ? $id : '[No ID]');
        }
        
        return $title;
    }
    
    /**
     * 
     * @param type $flag
     * @return type
     */
    public function screenOptions($flag) {
        if (AAM_Core_API::capabilityExists('show_screen_options')) {
            $flag = current_user_can('show_screen_options');
        } 
        
        if (AAM::isAAM()) {
            $flag = false;
        }
        
        return $flag;
    }
    
    /**
     * 
     * @param array $help
     * @param type $id
     * @param type $screen
     * @return array
     */
    public function helpOptions($help, $id, $screen) {
        if (AAM_Core_API::capabilityExists('show_help_tabs')) {
            if (!current_user_can('show_help_tabs')) {
                $screen->remove_help_tabs();
                $help = array();
            }
        } 
        
        if (AAM::isAAM()) {
            $screen->remove_help_tabs();
        }
        
        return $help;
    }
    
    /**
     * 
     * @return type
     */
    public function handleLogin() {
        $login = AAM_Core_Login::getInstance();

        echo wp_json_encode($login->execute());
        exit;
    }
    
    /**
     * 
     */
    public function adminInit() {
        $frame = AAM_Core_Request::get('aamframe');
        
        if ($frame && current_user_can('aam_manage_posts')) {
            echo AAM_Backend_View::getInstance()->renderAccessFrame();
            exit;
        }
        
        // Import/Export feature
        add_action('export_filters', array($this, 'renderExportFields'));
        add_filter('export_args', array($this, 'prepareExportArgs'));
        add_action('export_wp', array($this, 'exportSettings'));
        $this->registerAAMImporter();
    }
    
    /**
     * 
     * @param type $text
     * @return string
     */
    public function thankYou($text) {
        if (AAM::isAAM()) {
            $text  = '<span id="footer-thankyou">';
            $text .= '<b>Please help us</b> and submit your review <a href="';
            $text .= 'https://wordpress.org/support/plugin/advanced-access-manager/reviews/"';
            $text .= 'target="_blank"><i class="icon-star"></i>';
            $text .= '<i class="icon-star"></i><i class="icon-star"></i>';
            $text .= '<i class="icon-star"></i><i class="icon-star"></i></a>';
            $text .= '</span>';
        }
        
        return $text;
    }
    
    /**
     * 
     */
    public function registerLoginWidget() {
        register_widget('AAM_Backend_Widget_Login');
    }
    
    /**
     * 
     */
    protected function checkUserAccess() {
        $uid = get_current_user_id();
        
        if ($uid && AAM_Core_API::capabilityExists('access_dashboard')) {
            $caps = AAM::getUser()->allcaps;
            // If this is the AJAX call, still allow it because it will break a lot
            // of frontend stuff that depends on it
            if (empty($caps['access_dashboard']) && !defined('DOING_AJAX')) {
                AAM_Core_API::reject(
                    'backend', array('hook' => 'access_dashboard')
                );
            }
        }
    }
    
    /**
     * 
     */
    protected function checkUserSwitch() {
        if (AAM_Core_Request::get('action') === 'aam-switch-back') {
            $current  = get_current_user_id();
            $uid      = AAM_Core_API::getOption('aam-user-switch-' . $current);
            $redirect = admin_url('admin.php?page=aam&user=' . $current);
            
            check_admin_referer('aam-switch-' . $uid);
            
            wp_clear_auth_cookie();
            wp_set_auth_cookie( $uid, true );
            wp_set_current_user( $uid );
            
            AAM_Core_API::deleteOption('aam-user-switch-' . $current);
            
            wp_redirect($redirect);
            exit;
        }
    }
    
    /**
     * 
     */
    public function notification() {
        $uid = AAM_Core_API::getOption('aam-user-switch-' . get_current_user_id());
        
        if ($uid) {
            //get user's name
            $user  = new WP_User($uid);
            $name = $user->display_name ? $user->display_name : $user->user_nicename;
            
            //generate switch back URL
            $url = wp_nonce_url(
                    'index.php?action=aam-switch-back', 'aam-switch-' . $uid
            );
            
            echo '<div class="updated notice">';
            echo '<p style="padding: 10px; font-weight: 700; letter-spacing:0.5px;">';
            echo sprintf('Switch back to <a href="%s">%s</a>.', $url, esc_js($name));
            echo '</p></div>';
        }
    }
    
    /**
     * 
     */
    public function metabox() {
        global $post;
        
        $frontend = AAM_Core_Config::get('core.settings.frontendAccessControl', true);
        $backend  = AAM_Core_Config::get('core.settings.backendAccessControl', true);
        $api      = AAM_Core_Config::get('core.settings.apiAccessControl', true);
        
        $needAC  = ($frontend || $backend || $api);
        $allowed = current_user_can('aam_manage_posts');
        $notASP  = (!is_a($post, 'WP_Post') || ($post->post_type !== 'aam_policy'));
        
        if ($needAC && $allowed && $notASP) {
            add_meta_box(
                'aam-access-manager', 
                __('Access Manager', AAM_KEY), 
                array($this, 'renderPostMetabox'),
                null,
                'advanced',
                'high'
            );
        }
    }
    
    /**
     * 
     * @global WP_Post $post
     */
    public function registerPolicyDocMetabox() {
         global $post;
         
        if (is_a($post, 'WP_Post') && ($post->post_type === 'aam_policy')) {
            add_meta_box(
                'aam-policy', 
                __('Policy Document', AAM_KEY), 
                array($this, 'renderPolicyMetabox'),
                null,
                'normal',
                'high'
            );
            add_meta_box(
                'aam-policy-attached', 
                __('Policy Principals', AAM_KEY), 
                array($this, 'renderPolicyPrincipalMetabox'),
                null,
                'side'
            );
        }
    }
    
    /**
     * 
     * @global WP_Post $post
     */
    public function renderPolicyMetabox() {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            echo AAM_Backend_View::getInstance()->renderPolicyMetabox($post);
        }
    }
    
    /**
     * 
     * @global WP_Post $post
     */
    public function renderPolicyPrincipalMetabox() {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            echo AAM_Backend_View::getInstance()->renderPolicyPrincipalMetabox($post);
        }
    }
    
    /**
     * 
     * @global type $wp_admin_bar
     */
    public function cacheAdminBar() {
        global $wp_admin_bar;
        static $cache = null;
        
        $reflection = new ReflectionClass(get_class($wp_admin_bar));
        
        $prop = $reflection->getProperty('nodes');
        $prop->setAccessible(true);
        
        $nodes = $prop->getValue($wp_admin_bar);
        
        if (isset($nodes['root']) && is_null($cache)) {
            $cache = array();
            foreach($nodes['root']->children as $node) {
                $cache = array_merge($cache, $node->children);
            }
            
            // do some cleanup
            foreach($cache as $i => $node) {
                if ($node->id === 'menu-toggle') {
                    unset($cache[$i]);
                }
            }
        }
        
        return $cache;
    }
    
    /**
     * 
     * @global type $post
     */
    public function renderPostMetabox() {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            echo AAM_Backend_View::getInstance()->renderPostMetabox($post);
        }
    }
    
    /**
     * 
     * @param type $term
     */
    public function renderTermMetabox($term) {
        if (is_a($term, 'WP_Term') && is_taxonomy_hierarchical($term->taxonomy)) {
            $frontend = AAM_Core_Config::get('core.settings.frontendAccessControl', true);
            $backend  = AAM_Core_Config::get('core.settings.backendAccessControl', true);
            $api      = AAM_Core_Config::get('core.settings.apiAccessControl', true);

            if (($frontend || $backend || $api) && current_user_can('aam_manage_posts')) {
                echo AAM_Backend_View::getInstance()->renderTermMetabox($term);
            }
        }
    }
    
    /**
     * Handle Metabox initialization process
     *
     * @return void
     *
     * @access public
     */
    public function initMetaboxes() {
        global $post;

        if (AAM_Core_Request::get('init') === 'metabox') {
            //make sure that nobody is playing with screen options
            if (is_a($post, 'WP_Post')) {
                $screen = $post->post_type;
            } else {
                $screen_object = get_current_screen();
                $screen        = ($screen_object ? $screen_object->id : '');
            }
        
            $model = new AAM_Backend_Feature_Main_Metabox;
            $model->initialize($screen);
        }
    }
    
    /**
     * Add extra column to search in for User search
     *
     * @param array $columns
     *
     * @return array
     *
     * @access public
     */
    public function searchColumns($columns) {
        $columns[] = 'display_name';

        return $columns;
    }
    
    /**
     * 
     * @param type $actions
     * @param type $post
     * @return string
     */
    public function postRowActions($actions, $post) {
        if ($this->renderExternalUIFeature('aam_manage_posts')) {
            $url = admin_url('admin.php?page=aam&oid=' . $post->ID . '&otype=post#post');

            $actions['aam']  = '<a href="' . $url . '" target="_blank">';
            $actions['aam'] .= __('Access', AAM_KEY) . '</a>';
        }
        
        return $actions;
    }
    
    /**
     * 
     * @param type $actions
     * @param type $term
     * @return string
     */
    public function tagRowActions($actions, $term) {
        if ($this->renderExternalUIFeature('aam_manage_posts')) {
            $oid = $term->term_id . '|' . $term->taxonomy;
            $url = admin_url('admin.php?page=aam&oid=' . $oid . '&otype=term#post');

            $actions['aam']  = '<a href="' . $url . '" target="_blank">';
            $actions['aam'] .= __('Access', AAM_KEY) . '</a>';
        }
        
        return $actions;
    }
    
    /**
     * Add "Manage Access" action
     * 
     * Add additional action to the user list table.
     * 
     * @param array   $actions
     * @param WP_User $user
     * 
     * @return array
     * 
     * @access public
     */
    public function userActions($actions, $user) {
        if ($this->renderExternalUIFeature('aam_manage_users') 
                    || $this->renderExternalUIFeature('list_users')) {
            $url = admin_url('admin.php?page=aam&user=' . $user->ID);

            $actions['aam']  = '<a href="' . $url . '" target="_blank">';
            $actions['aam'] .= __('Access', AAM_KEY) . '</a>';
        }
        
        return $actions;
    }
    
    /**
     * 
     * @param type $cap
     * @return type
     */
    protected function renderExternalUIFeature($cap) {
        $frontend       = AAM_Core_Config::get('core.settings.frontendAccessControl', true);
        $backend        = AAM_Core_Config::get('core.settings.backendAccessControl', true);
        $api            = AAM_Core_Config::get('core.settings.apiAccessControl', true);
        $aamManager     = current_user_can('aam_manager');
        $featureManager = current_user_can($cap);
        
        return ($frontend || $backend || $api) && $aamManager && $featureManager;
    }

    /**
     * Print javascript libraries
     *
     * @return void
     *
     * @access public
     */
    public function printJavascript() {
        if (AAM::isAAM()) {
            wp_enqueue_script('aam-vendor', AAM_MEDIA . '/js/vendor.js');
            wp_enqueue_script('aam-main', AAM_MEDIA . '/js/aam-5.9.2.js');
            
            //add plugin localization
            $this->printLocalization('aam-main');
        }
    }
    
    /**
     * 
     * @global type $menu
     * @global type $submenu
     */
    public function printFooterJavascript() {
        global $menu, $submenu;
        
        if (AAM::isAAM()) {
            $script  = '<script type="text/javascript">';
            $script .= 'var aamEnvData = ' . wp_json_encode(array(
                'menu'    => base64_encode(json_encode($menu)),
                'submenu' => base64_encode(json_encode($submenu)),
                'toolbar' => base64_encode(json_encode($this->cacheAdminBar()))
            )) ;
            $script .= '</script>';

            echo $script;
        }
    }
    
    /**
     * Print plugin localization
     * 
     * @param string $localKey
     * 
     * @return void
     * 
     * @access protected
     */
    protected function printLocalization($localKey) {
        $subject  = AAM_Backend_Subject::getInstance();
        $endpoint = getenv('AAM_ENDPOINT');
        
        $locals = array(
            'nonce'    => wp_create_nonce('aam_ajax'),
            'ajaxurl'  => esc_url(admin_url('admin-ajax.php')),
            'ui'       => AAM_Core_Request::get('aamframe', 'main'),
            'url' => array(
                'site'      => esc_url(admin_url('index.php')),
                'editUser'  => esc_url(admin_url('user-edit.php')),
                'addUser'   => esc_url(admin_url('user-new.php')),
                'addPolicy' => esc_url(admin_url('post-new.php?post_type=aam_policy'))
            ),
            'level'     => AAM::getUser()->getMaxLevel(),
            'subject'   => array(
                'type'  => $subject->getUID(),
                'id'    => $subject->getId(),
                'name'  => $subject->getName(),
                'level' => $subject->getMaxLevel(),
                'blog'  => get_current_blog_id()
            ),
            'system' => array(
                'domain'      => wp_parse_url(site_url(), PHP_URL_HOST),
                'uid'         => AAM_Core_API::getOption('aam-uid', null, 'site'),
                'apiEndpoint' => ($endpoint ? $endpoint : AAM_Core_Server::SERVER_URL)
            ),
            'translation' => AAM_Backend_View_Localization::get(),
            'caps'        => array(
                'create_roles' => current_user_can('aam_create_roles'),
                'create_users' => current_user_can('create_users')
            )
        );
        
        wp_localize_script($localKey, 'aamLocal', $locals);
    }
    
    /**
     * Print necessary styles
     *
     * @return void
     *
     * @access public
     */
    public function printStylesheet() {
        if (AAM::isAAM()) {
            wp_enqueue_style('aam-bt', AAM_MEDIA . '/css/bootstrap.min.css');
            wp_enqueue_style('aam-db', AAM_MEDIA . '/css/datatables.min.css');
            wp_enqueue_style('aam-main', AAM_MEDIA . '/css/aam.css');
        }
    }

    /**
     * Register Admin Menu
     *
     * @return void
     *
     * @access public
     */
    public function adminMenu() {
        if (AAM_Core_Console::count() && current_user_can('aam_show_notifications')) {
            $counter = '&nbsp;<span class="update-plugins">'
                     . '<span class="plugin-count">' . AAM_Core_Console::count()
                     . '</span></span>';
        } else {
            $counter = '';
        }
        
        //register the menu
        add_menu_page(
            'AAM', 
            'AAM' . $counter, 
            'aam_manager', 
            'aam', 
            array($this, 'renderPage'), 
            AAM_MEDIA . '/active-menu.svg'
        );
        
        // Access policy page
        add_submenu_page(
            'aam', 
            'Access Policies', 
            'Access Policies', 
            AAM_Core_Config::get('policy.capability', 'aam_manage_policy'), 
            'edit.php?post_type=aam_policy'
        );

        $type = get_post_type_object('aam_policy');
        if (current_user_can($type->cap->create_posts)) {
            add_submenu_page(
                'aam', 
                'Add New Policies', 
                'Add New Policies', 
                $type->cap->create_posts, 
                'post-new.php?post_type=aam_policy'
            );
        }

    }
    
    /**
     * Render Main Content page
     *
     * @return void
     *
     * @access public
     */
    public function renderPage() {
        echo AAM_Backend_View::getInstance()->renderPage();
    }
    
    /**
     * Render list of AAM Features
     *
     * Must be separate from Ajax call because WordPress ajax does not load 
     * a lot of UI stuff like admin menu
     *
     * @return void
     *
     * @access public
     */
    public function renderContent() {
        check_ajax_referer('aam_ajax');
        
        // flush any output buffer
        @ob_clean();
        
        if (current_user_can('aam_manager')) {
            $response = AAM_Backend_View::getInstance()->renderContent(
                    AAM_Core_Request::post('uiType', 'main')
            );
            
            $accept = AAM_Core_Request::server('HTTP_ACCEPT_ENCODING');
            header('Content-Type: text/html; charset=UTF-8');
            
            $zlib       = strtolower(ini_get('zlib.output_compression'));
            $compressed = count(array_intersect(
                array('zlib output compression', 'ob_gzhandler'),
                ob_list_handlers()
            )) > 0;
            
            if (in_array($zlib, array('1', 'on'), true) && !empty($accept)) {
                header('Vary: Accept-Encoding'); // Handle proxies
                
                if ( false !== stripos($accept, 'gzip') && function_exists('gzencode') ) {
                    header('Content-Encoding: gzip');
                    $response = ($compressed ? $response : gzencode($response, 3));
                }
            }
            
            echo $response;
        } else {
            echo __('Access Denied', AAM_KEY);
        }
        
        exit();
    }

    /**
     * Handle Ajax calls to AAM
     *
     * @return void
     *
     * @access public
     */
    public function ajax() {
        check_ajax_referer('aam_ajax');

        //clean buffer to make sure that nothing messing around with system
        while (@ob_end_clean()){}
        
        //process ajax request
        if (current_user_can('aam_manager')) {
            echo AAM_Backend_View::getInstance()->processAjax();
        } else {
            echo __('Access Denied', AAM_KEY);
        }
        
        exit();
    }
    
    /**
     * Bootstrap the manager
     * 
     * @return AAM_Backend_View
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    /**
     * Get instance of itself
     * 
     * @return AAM_Backend_View
     * 
     * @access public
     */
    public static function getInstance() {
        return self::bootstrap();
    }

}