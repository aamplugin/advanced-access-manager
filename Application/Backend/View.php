<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend view manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_View {

    /**
     * Instance of itself
     * 
     * @var AAM_Backend_View
     * 
     * @access private 
     */
    private static $_instance = null;

    /**
     * Construct the view object
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //register default features
        AAM_Backend_Feature_Main_Menu::register();
        AAM_Backend_Feature_Main_Metabox::register();
        AAM_Backend_Feature_Main_Capability::register();
        AAM_Backend_Feature_Main_Post::register();
        AAM_Backend_Feature_Main_Redirect::register();
        AAM_Backend_Feature_Main_LoginRedirect::register();
        AAM_Backend_Feature_Main_LogoutRedirect::register();
        AAM_Backend_Feature_Main_404Redirect::register();
        
        AAM_Backend_Feature_Settings_Core::register();
        AAM_Backend_Feature_Settings_Content::register();
        AAM_Backend_Feature_Settings_Tools::register();
        
        //feature registration hook
        do_action('aam-feature-registration-action');
    }

    /**
     * Run the Manager
     *
     * @return string
     *
     * @access public
     */
    public function renderPage() {
        ob_start();
        require_once(dirname(__FILE__) . '/phtml/index.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * Run the Manager
     *
     * @return string
     *
     * @access public
     */
    public function renderAccessFrame() {
        ob_start();
        require_once(dirname(__FILE__) . '/phtml/frame.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * 
     * @param type $post
     * @return type
     */
    public function renderPostMetabox($post) {
        ob_start();
        require_once(dirname(__FILE__) . '/phtml/post-metabox.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * 
     * @param type $term
     * @return type
     */
    public function renderTermMetabox($term) {
        ob_start();
        require_once(dirname(__FILE__) . '/phtml/term-metabox.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Process the ajax call
     *
     * @return string
     *
     * @access public
     */
    public function processAjax() {
        $response = null;
        
        $action = AAM_Core_Request::request('sub_action');
        $parts  = explode('.', $action);
        
        if (method_exists($this, $parts[0])) {
            $response = call_user_func(array($this, $parts[0]));
        } elseif (count($parts) == 2) { //cover the Model.method pattern
            try {
                $classname = 'AAM_Backend_Feature_' . $parts[0];
                if (class_exists($classname)) {
                    $response  = call_user_func(array(new $classname, $parts[1]));
                }
            } catch (Exception $e) {
                $response = $e->getMessage();
            }
        }
        
        return apply_filters(
                'aam-ajax-filter', 
                $response, 
                AAM_Backend_Subject::getInstance()->get(), 
                $action
        );
    }
    
    /**
     * Render the Main Control Area
     *
     * @param string $type
     * 
     * @return void
     *
     * @access public
     */
    public function renderContent($type = 'main') {
        ob_start();
        if ($type == 'extensions') {
            AAM_Backend_Feature_Extension_Manager::getInstance()->render();
        } else {
            require_once(dirname(__FILE__) . '/phtml/main-panel.phtml');
        }
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * 
     * @param type $partial
     * @return type
     */
    public function loadPartial($partial) {
        ob_start();
        require_once(dirname(__FILE__) . '/phtml/partial/' . $partial);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Save AAM options
     * 
     * Important notice! This function excepts "value" to be only boolean value
     *
     * @return string
     *
     * @access public
     */
    public function save() {
        $object   = trim(AAM_Core_Request::post('object'));
        $objectId = intval(AAM_Core_Request::post('objectId', 0));
        
        $param = AAM_Core_Request::post('param');
        $value = filter_input(INPUT_POST, 'value');
        
        $result = AAM_Backend_Subject::getInstance()->save(
                $param, $value, $object, $objectId
        );

        return json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * 
     * @return type
     */
    public function reset() {
        return AAM_Backend_Subject::getInstance()->resetObject(
                AAM_Core_Request::post('object')
        );
    }
    
    /**
     * 
     * @return type
     */
    public function switchToUser() {
        $response = array(
                'status' => 'failure', 
                'reason' => 'You are not allowed to switch to this user'
        );
        
        if (current_user_can('aam_switch_users')) { 
            $user  = new WP_User(AAM_Core_Request::post('user'));
            $max   = AAM_Core_API::maxLevel(wp_get_current_user()->allcaps);

            if ($max >= AAM_Core_API::maxLevel($user->allcaps)) {
                AAM_Core_API::updateOption(
                        'aam-user-switch-' . $user->ID, get_current_user_id()
                );

                wp_clear_auth_cookie();
                wp_set_auth_cookie( $user->ID, true );
                wp_set_current_user( $user->ID );

                $response = array('status' => 'success', 'redirect' => admin_url());
            }
        }
        
        return json_encode($response);
    }
    
    /**
     * Get instance of itself
     * 
     * @return AAM_Backend_View
     * 
     * @access public
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}