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
        
        //print required JS & CSS
        add_action('admin_print_scripts', array($this, 'printJavascript'));
        add_action('admin_print_styles', array($this, 'printStylesheet'));
        
        //map AAM UI specific capabilities
        add_filter('map_meta_cap', array($this, 'mapMetaCap'), 10, 4);
        
        //user profile update action
        add_action('profile_update', array($this, 'profileUpdate'), 10, 2);
        
        //post title decorator
        add_filter('the_title', array($this, 'theTitle'), 999, 2);
        
        //screen options & contextual help hooks
        add_filter('screen_options_show_screen', array($this, 'screenOptions'));
        add_filter('contextual_help', array($this, 'helpOptions'), 10, 3);

        //manager Admin Menu
        if (is_multisite() && is_network_admin()) {
            //register AAM in the network admin panel
            add_action('network_admin_menu', array($this, 'adminMenu'));
        } else {
            add_action('admin_menu', array($this, 'adminMenu'));
            add_action('all_admin_notices', array($this, 'notification'));
        }
        
        if (AAM_Core_Config::get('render-access-metabox', true)) {
            add_action('edit_category_form_fields', array($this, 'renderTermMetabox'), 1);
            add_action('edit_link_category_form_fields', array($this, 'renderTermMetabox'), 1);
            add_action('edit_tag_form_fields', array($this, 'renderTermMetabox'), 1);
            
            //register custom access control metabox
            add_action('add_meta_boxes', array($this, 'metabox'));
        }
        
        //manager AAM Ajax Requests
        add_action('wp_ajax_aam', array($this, 'ajax'));
        //manager AAM Features Content rendering
        add_action('admin_action_aamc', array($this, 'renderContent'));
        //manager user search and authentication control
        add_filter('user_search_columns', array($this, 'searchColumns'));
        
        //manager WordPress metaboxes
        add_action("in_admin_header", array($this, 'initMetaboxes'), 999);
        
        if (AAM_Core_Config::get('show-access-link', true)) {
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
        
        //register login widget
        if (AAM_Core_Config::get('secure-login', true)) {
            add_action('widgets_init', array($this, 'registerLoginWidget'));
            add_action('wp_ajax_nopriv_aamlogin', array($this, 'handleLogin'));
        }
        
        //register backend hooks and filters
        if (AAM_Core_Config::get('backend-access-control', true)) {
            AAM_Backend_Filter::register();
        }
        
        AAM_Extension_Repository::getInstance()->hasUpdates();
    }
    
    /**
     * 
     * @param type $caps
     * @param type $cap
     * @return type
     */
    public function mapMetaCap($caps, $cap) {
        if (in_array($cap, AAM_Backend_Feature_Main_Capability::$groups['aam'])) {
            if (!AAM_Core_API::capabilityExists($cap)) {
                $caps = array(AAM_Core_Config::get('page.capability', 'administrator'));
            }
        }
        
        return $caps;
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
        
        //role changed?
        if (implode('', $user->roles) != implode('', $old->roles)) {
            AAM_Core_Cache::clear($id);
            
            //check if role has expiration data set
            $role   = (is_array($user->roles) ? $user->roles[0] : '');
            $expire = AAM_Core_API::getOption("aam-role-{$role}-expiration", '');
            
            if ($expire) {
                update_user_option($id, "aam-original-roles", $old->roles);
                update_user_option($id, "aam-role-expires", strtotime($expire));
            }
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
            $flag = AAM::getUser()->hasCapability('show_screen_options');
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
            if (!AAM::getUser()->hasCapability('show_help_tabs')) {
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

        echo json_encode($login->execute());
        exit;
    }
    
    /**
     * 
     */
    public function adminInit() {
        $user  = AAM::getUser();
        $frame = AAM_Core_Request::get('aamframe');
        
        if ($frame && $user->hasCapability('aam_manage_posts')) {
            echo AAM_Backend_View::getInstance()->renderAccessFrame();
            exit;
        }
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
            if (empty($caps['access_dashboard'])) {
                //also additionally check for AJAX calls
                if (defined('DOING_AJAX') && empty($caps['allow_ajax_calls'])) {
                    AAM_Core_API::reject(
                            'backend', array('hook' => 'access_dashboard')
                    );
                } elseif (!defined('DOING_AJAX')) {
                    AAM_Core_API::reject(
                            'backend', array('hook' => 'access_dashboard')
                    );
                }
            }
        }
    }
    
    /**
     * 
     */
    protected function checkUserSwitch() {
        if (AAM_Core_Request::get('action') == 'aam-switch-back') {
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
            
            $style = 'padding: 10px; font-weight: 700; letter-spacing:0.5px;';
            
            echo '<div class="updated notice">';
            echo '<p style="' . $style . '">';
            echo sprintf('Switch back to <a href="%s">%s</a>.', $url, $name);
            echo '</p></div>';
        }
    }
    
    /**
     * 
     */
    public function metabox() {
        $frontend = AAM_Core_Config::get('frontend-access-control', true);
        $backend  = AAM_Core_Config::get('backend-access-control', true);
        
        if (($frontend || $backend) && AAM::getUser()->hasCapability('aam_manage_posts')) {
            add_meta_box(
                'aam-acceess-manager', 
                __('Access Manager', AAM_KEY) . ' <small style="color:#999999;">by AAM plugin</small>', 
                array($this, 'renderPostMetabox'),
                null,
                'advanced',
                'high'
            );
        }
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
            $frontend = AAM_Core_Config::get('frontend-access-control', true);
            $backend  = AAM_Core_Config::get('backend-access-control', true);

            if (($frontend || $backend) && AAM::getUser()->hasCapability('aam_manage_posts')) {
                echo AAM_Backend_View::getInstance()->renderTermMetabox($term);
            }
        }
    }
    
    /**
     * Hanlde Metabox initialization process
     *
     * @return void
     *
     * @access public
     */
    public function initMetaboxes() {
        global $post;

        if (AAM_Core_Request::get('init') == 'metabox') {
            //make sure that nobody is playing with screen options
            if (is_a($post, 'WP_Post')) {
                $screen = $post->post_type;
            } elseif ($screen_object = get_current_screen()) {
                $screen = $screen_object->id;
            } else {
                $screen = '';
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
        if ($this->renderExternalUIFeature('list_users')) {
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
        $frontend       = AAM_Core_Config::get('frontend-access-control', true);
        $backend        = AAM_Core_Config::get('backend-access-control', true);
        $aamManager     = AAM::getUser()->hasCapability('aam_manager');
        $featureManager = AAM::getUser()->hasCapability($cap);
        
        return ($frontend || $backend) && $aamManager && $featureManager;
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
            wp_enqueue_script('aam-main', AAM_MEDIA . '/js/aam.js');
            
            //add plugin localization
            $this->printLocalization('aam-main');
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
        $subject = AAM_Backend_Subject::getInstance();
        
        $locals = array(
            'nonce'   => wp_create_nonce('aam_ajax'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'url' => array(
                'site'     => admin_url('index.php'),
                'editUser' => admin_url('user-edit.php'),
                'addUser'  => admin_url('user-new.php')
            ),
            'level'     => AAM_Core_API::maxLevel(wp_get_current_user()->allcaps),
            'subject'   => array(
                'type'  => $subject->getUID(),
                'id'    => $subject->getId(),
                'name'  => $subject->getName(),
                'level' => $subject->getMaxLevel(),
                'blog'  => get_current_blog_id()
            ),
            'translation' => AAM_Backend_View_Localization::get(),
            'caps'        => array(
                'create_roles' => current_user_can('aam_create_roles'),
                'create_users' => current_user_can('create_users')
            )
        );
        
        if (AAM_Core_Request::get('aamframe')) {
            $locals['ui'] = 'post';
        }
        
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
        if (AAM_Core_Console::count()) {
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
        
        if (AAM::getUser()->hasCapability('aam_manager')) {
            echo AAM_Backend_View::getInstance()->renderContent(
                    AAM_Core_Request::post('uiType', 'main')
            );
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
        if (AAM::getUser()->hasCapability('aam_manager')) {
            echo AAM_Backend_View::getInstance()->processAjax();
        } else {
            echo __('Access Denied', AAM_KEY);
        }
        
        exit();
    }

    /**
     * Bootstrap the manager
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
    }
    
    /**
     * Get instance of itself
     * 
     * @return AAM_Backend_View
     * 
     * @access public
     */
    public static function getInstance() {
        self::bootstrap();

        return self::$_instance;
    }

}