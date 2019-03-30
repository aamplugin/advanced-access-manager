<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend core settings
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_Core extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        if (!current_user_can('aam_manage_settings')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_settings'));
        }
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'settings/core.phtml';
    }
    
    /**
     * 
     * @return type
     */
    protected function getList() {
        $settings = array(
            'core.settings.getStarted' => array(
                'title' => __('Get Started Tab', AAM_KEY),
                'descr' => __('Display the "Get Started" feature on the Main panel. You can disable this feature to remove the extra tab when you get familiar with core access control concepts.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.getStarted', true)
            ),
            'core.settings.editCapabilities' => array(
                'title' => __('Edit/Delete Capabilities', AAM_KEY),
                'descr' => AAM_Backend_View_Helper::preparePhrase('Allow to edit or delete any existing capability on the Capabilities tab. [Warning!] For experienced users only. Changing or deleting capability may result in loosing access to some features or even the entire website.', 'b'),
                'value' => AAM_Core_Config::get('core.settings.editCapabilities', true)
            ),
            'core.settings.backendAccessControl' => array(
                'title' => __('Backend Access Control', AAM_KEY),
                'descr' => __('Allow AAM to manage access to the backend. Keep this option disabled if there is no needs to restrict backend features for other users.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.backendAccessControl', true)
            ),
            'core.settings.frontendAccessControl' => array(
                'title' => __('Frontend Access Control', AAM_KEY),
                'descr' => __('Allow AAM to manage access to the frontend. Keep this option disabled if there is no needs to restrict frontend resources for users and visitors.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.frontendAccessControl', true)
            ),
            'core.settings.apiAccessControl' => array(
                'title' => __('API Access Control', AAM_KEY),
                'descr' => __('Allow AAM to manage access to the website resources that are invoked with WordPress core APIs. Keep this option disabled if there is no needs to restrict API access.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.apiAccessControl', true)
            ),
            'ui.settings.renderAccessMetabox' => array(
                'title' => __('Render Access Manager Metabox', AAM_KEY),
                'descr' => __('Render Access Manager metabox on all post and term edit pages. Access Manager metabox is the quick way to manage access to any post or term without leaving an edit page.', AAM_KEY),
                'value' => AAM_Core_Config::get('ui.settings.renderAccessMetabox', true),
            ),
            'ui.settings.renderAccessActionLink' => array(
                'title' => __('Render Access Link', AAM_KEY),
                'descr' => __('Render Access shortcut link under any post, page, custom post type, category, custom taxonomy title or user name.', AAM_KEY),
                'value' => AAM_Core_Config::get('ui.settings.renderAccessActionLink', true),
            ),
            'core.settings.secureLogin' => array(
                'title' => __('Secure Login', AAM_KEY),
                'descr' => __('AAM comes with its own user login handler. With this feature you can add AJAX login widget to your frontend page that significantly enhance your website security.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.secureLogin', true)
            ),
            'core.settings.xmlrpc' => array(
                'title' => __('XML-RPC WordPress API', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Remote procedure call (RPC) interface is used to manage WordPress website content and features. For more information check %sXML-RPC Support%s article.', 'b'), '<a href="https://codex.wordpress.org/XML-RPC_Support">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.xmlrpc', true)
            ),
            'core.settings.restful' => array(
                'title' => __('RESTful WordPress API', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('RESTful interface that is used to manage WordPress website content and features. For more information check %sREST API handbook%s.', 'b'), '<a href="https://developer.wordpress.org/rest-api/">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.restful', true)
            ),
            'core.settings.jwtAuthentication' => array(
                'title' => __('JWT Authentication', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('[Note!] PHP 5.4 or higher is required for this feature. Enable the ability to authenticate user with WordPress RESTful API and JWT token. For more information, check %sHow to authenticate WordPress user with JWT token%s article', 'b'), '<a href="https://aamplugin.com/article/how-to-authenticate-wordpress-user-with-jwt-token">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.jwtAuthentication', true)
            ),
            'core.settings.multiSubject' => array(
                'title' => __('Multiple Roles Support', AAM_KEY),
                'descr' => sprintf(__('Enable support for multiple roles per use. The final access settings or general settings will be computed based on the mergin preferences. For more information check %sWordPress access control for users with multiple roles%s article.', AAM_KEY), '<a href="https://aamplugin.com/article/wordpress-access-control-for-users-with-multiple-roles">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.multiSubject', false)
            ),
            'core.settings.extensionSupport' => array(
                'title' => __('Support AAM Extensions', AAM_KEY),
                'descr' => __('AAM comes with the limited list of premium and free extensions that significantly enhance AAM behavior. You can disable support for AAM extension and any already installed extension will no longer be loaded during the website execution as well as website administrator will not be able to install new extensions.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.extensionSupport', true)
            ),
            'core.settings.cron' => array(
                'title' => __('AAM Cron Job', AAM_KEY),
                'descr' => __('AAM cron job executes periodically (typically once a day) to check for available updates for already installed extensions. Cron job is not executed if there are no installed extensions.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.cron', true)
            )
        );
        
        return apply_filters('aam-settings-filter', $settings, 'core');
    }
    
    /**
     * Register Contact/Hire feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'settings-core',
            'position'   => 1,
            'title'      => __('Core Settings', AAM_KEY),
            'capability' => 'aam_manage_settings',
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}