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
 * @author  Vasyl Martyniuk <vasyl@vasyltech.com>
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
        AAM_Backend_Feature_Main_GetStarted::register();
        AAM_Backend_Feature_Main_Policy::register();
        AAM_Backend_Feature_Main_Menu::register();
        AAM_Backend_Feature_Main_Toolbar::register();
        AAM_Backend_Feature_Main_Metabox::register();
        AAM_Backend_Feature_Main_Capability::register();
        AAM_Backend_Feature_Main_Route::register();
        AAM_Backend_Feature_Main_Post::register();
        AAM_Backend_Feature_Main_Redirect::register();
        AAM_Backend_Feature_Main_LoginRedirect::register();
        AAM_Backend_Feature_Main_LogoutRedirect::register();
        AAM_Backend_Feature_Main_404Redirect::register();
        AAM_Backend_Feature_Main_Uri::register();
        
        AAM_Backend_Feature_Settings_Core::register();
        AAM_Backend_Feature_Settings_Content::register();
        AAM_Backend_Feature_Settings_Security::register();
        AAM_Backend_Feature_Settings_ConfigPress::register();
        
        //feature registration hook
        do_action('aam-feature-registration-action');
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
        
        if (count($parts) === 2) {
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
     * Run the Manager
     *
     * @return string
     *
     * @access public
     */
    public function renderPage() {
        return $this->loadTemplate(dirname(__FILE__) . '/phtml/index.phtml');
    }
    
    /**
     * Run the Manager
     *
     * @return string
     *
     * @access public
     */
    public function renderAccessFrame() {
        return $this->loadTemplate(
            dirname(__FILE__) . '/phtml/metabox/metabox-content.phtml'
        );
    }
    
    /**
     * 
     * @param type $post
     * @return type
     */
    public function renderPostMetabox($post) {
        return $this->loadTemplate(
            dirname(__FILE__) . '/phtml/metabox/post-metabox.phtml',
            (object) array('post' => $post)
        );
    }
    
    /**
     * 
     * @param type $post
     * @return type
     */
    public function renderPolicyMetabox($post) {
        return $this->loadTemplate(
            dirname(__FILE__) . '/phtml/metabox/policy-metabox.phtml',
            (object) array('post' => $post)
        );
    }
    
    /**
     * 
     * @param type $post
     * @return type
     */
    public function renderPolicyPrincipalMetabox($post) {
        return $this->loadTemplate(
            dirname(__FILE__) . '/phtml/metabox/policy-principal-metabox.phtml',
            (object) array('post' => $post)
        );
    }
    
    /**
     * 
     * @param type $term
     * @return type
     */
    public function renderTermMetabox($term) {
        return $this->loadTemplate(
            dirname(__FILE__) . '/phtml/metabox/term-metabox.phtml',
            (object) array('term' => $term)
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
        $content = apply_filters('aam-ui-content-filter', null, $type);
        
        if (is_null($content) && current_user_can('aam_manager')) {
            ob_start();
            if ($type === 'extensions' && current_user_can('aam_manage_settings')) {
                AAM_Backend_Feature_Extension_Manager::getInstance()->render();
            } elseif ($type === 'postform' && current_user_can('aam_manage_posts')) {
                echo AAM_Backend_Feature_Main_Post::renderAccessForm();
            } else {
                require_once dirname(__FILE__) . '/phtml/main-panel.phtml';
            }
            $content = ob_get_contents();
            ob_end_clean();
        }
        
        return $content;
    }
    
    /**
     * 
     * @param type $partial
     * @return type
     */
    public function loadPartial($partial) {
        return $this->loadTemplate(dirname(__FILE__) . '/phtml/partial/' . $partial);
    }
    
    /**
     * Load template
     * 
     * @param string $filepath
     * 
     * @return string
     * 
     * @access protected
     */
    protected function loadTemplate($filepath, $args = null) {
        ob_start();
        
        require_once $filepath;
        $content = ob_get_contents();
        
        ob_end_clean();

        return $content;
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