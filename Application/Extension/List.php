<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

class AAM_Extension_List {
    
    /**
     * 
     * @return type
     */
    public static function get() {
        return array(
            'AAM_COMPLETE_PACKAGE' => array(
                'title'       => 'Complete Package',
                'id'          => 'AAM_COMPLETE_PACKAGE',
                'type'        => 'commercial',
                'description' => 'Get the complete list of all available premium extensions in one package. Any new premium extensions in the future will be available with updates for no additional cost.',
                'url'         => 'https://aamplugin.com/complete-package',
                'version'     => (defined('AAM_COMPLETE_PACKAGE') ? constant('AAM_COMPLETE_PACKAGE') : null)
            ),
            'AAM_PLUS_PACKAGE' => array(
                'title'       => 'Plus Package',
                'id'          => 'AAM_PLUS_PACKAGE',
                'type'        => 'commercial',
                'description' => 'The best selling extension that has the most advanced content management features for WordPress CMS. Manage accsss to any post, page, custom post type, category, custom hierarchical taxonomy or define the default access to all.',
                'url'         => 'https://aamplugin.com/extension/plus-package',
                'version'     => (defined('AAM_PLUS_PACKAGE') ? constant('AAM_PLUS_PACKAGE') : null)
            ),
            'AAM_IP_CHECK' => array(
                'title'       => 'IP Check',
                'id'          => 'AAM_IP_CHECK',
                'type'        => 'commercial',
                'description' => 'This extension was designed to manage access to your entire website based on visitor\'s geo-location, refered host or IP address.',
                'url'         => 'https://aamplugin.com/extension/ip-check',
                'version'     => (defined('AAM_IP_CHECK') ? constant('AAM_IP_CHECK') : null)
            ),
            'AAM_ROLE_HIERARCHY' => array(
                'title'       => 'Role Hierarchy',
                'id'          => 'AAM_ROLE_HIERARCHY',
                'type'        => 'commercial',
                'description' => 'This extension alters default WordPress linear role system and give you the ability to create complex role hierarchy tree where all access settings are automatically inherited from parent roles.',
                'url'         => 'https://aamplugin.com/extension/role-hierarchy',
                'version'     => (defined('AAM_ROLE_HIERARCHY') ? constant('AAM_ROLE_HIERARCHY') : null)
            ),
            'AAM_ECOMMERCE' => array(
                'title'       => 'E-Commerce',
                'id'          => 'AAM_ECOMMERCE',
                'type'        => 'commercial',
                'new'         => true,
                'description' => 'Start selling access to your website content. This extension gives ability to define the list of E-Commerce products that you can bind with any content on your website. The properly configured AAM Payment widget allows any authenticated user to purchase access with credit/debig card or PayPal. Braintree and Stripe gateways are used to handle actual purchase.',
                'url'         => 'https://aamplugin.com/extension/ecommerce',
                'version'     => (defined('AAM_ECOMMERCE') ? constant('AAM_ECOMMERCE') : null)
            ),
            'AAM_PAYMENT' => array(
                'title'       => 'Payment',
                'id'          => 'AAM_PAYMENT',
                'type'        => 'commercial',
                'description' => AAM_Backend_View_Helper::preparePhrase('[Deprecated!]. The extension is deprecated and replaces with more sophisticated E-Commerce extension. If you already purchased it, please contact us to upgrade your license for no additional cost.', 'b'),
                'url'         => 'https://aamplugin.com/extension/ecommerce',
                'version'     => (defined('AAM_PAYMENT') ? constant('AAM_PAYMENT') : null)
            ),
            'AAM_MULTISITE' => array(
                'title'       => 'Multisite',
                'id'          => 'AAM_MULTISITE',
                'type'        => 'GNU',
                'license'     => 'AAMMULTISITE',
                'description' => 'Convenient way to navigate between different sites in the Network Admin Panel.',
                'version'     => (defined('AAM_MULTISITE') ? constant('AAM_MULTISITE') : null)
            ),
            'AAM_CONFIGPRESS' => array(
                'title'       => 'ConfigPress',
                'id'          => 'AAM_CONFIGPRESS',
                'type'        => 'GNU',
                'license'     => 'AAMCONFIGPRESS',
                'description' => 'Extension to manage AAM core functionality with advanced configuration settings.',
                'version'     => (defined('AAM_CONFIGPRESS') ? constant('AAM_CONFIGPRESS') : null)
            ),
            'AAM_USER_ACTIVITY' => array(
                'title'       => 'User Activities',
                'id'          => 'AAM_USER_ACTIVITY',
                'type'        => 'GNU',
                'license'     => 'AAMUSERACTIVITY',
                'description' => 'Track any kind of user or visitor activity on your website. <a href="https://aamplugin.com/help/how-to-track-any-wordpress-user-activity" target="_blank">Read more.</a>',
                'version'     => (defined('AAM_USER_ACTIVITY') ? constant('AAM_USER_ACTIVITY') : null)
            ),
        );
    }
}