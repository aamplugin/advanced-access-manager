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
                'description' => 'Get the complete list of all available premium extensions in one package. Any new premium extensions in the future will be available for no additional cost.',
                'url'         => 'https://aamplugin.com/complete-package',
                'version'     => (defined('AAM_COMPLETE_PACKAGE') ? constant('AAM_COMPLETE_PACKAGE') : null),
                'latest'      => '3.8.5' 
            ),
            'AAM_PLUS_PACKAGE' => array(
                'title'       => 'Plus Package',
                'id'          => 'AAM_PLUS_PACKAGE',
                'type'        => 'commercial',
                'description' => 'The best selling extension with the most advanced content management features for the WordPress CMS. Manage granular access to any post, page, custom post type, category, custom hierarchical taxonomy or define the default access to all your content for all users, roles and visitors.',
                'url'         => 'https://aamplugin.com/extension/plus-package',
                'version'     => (defined('AAM_PLUS_PACKAGE') ? constant('AAM_PLUS_PACKAGE') : null),
                'latest'      => '3.7.8'
            ),
            'AAM_IP_CHECK' => array(
                'title'       => 'IP Check',
                'id'          => 'AAM_IP_CHECK',
                'type'        => 'commercial',
                'description' => 'Manage access to your entire website based on visitor\'s geo-location, refered host or IP address.',
                'url'         => 'https://aamplugin.com/extension/ip-check',
                'version'     => (defined('AAM_IP_CHECK') ? constant('AAM_IP_CHECK') : null),
                'latest'      => '2.0'
            ),
            'AAM_ROLE_HIERARCHY' => array(
                'title'       => 'Role Hierarchy',
                'id'          => 'AAM_ROLE_HIERARCHY',
                'type'        => 'commercial',
                'description' => 'This extension alters default WordPress linear role system and give you the ability to create complex role hierarchy tree where all access settings are automatically inherited from parent roles.',
                'url'         => 'https://aamplugin.com/extension/role-hierarchy',
                'version'     => (defined('AAM_ROLE_HIERARCHY') ? constant('AAM_ROLE_HIERARCHY') : null),
                'latest'      => '1.4'
            ),
            'AAM_ECOMMERCE' => array(
                'title'       => 'E-Commerce',
                'id'          => 'AAM_ECOMMERCE',
                'type'        => 'commercial',
                'new'         => true,
                'description' => 'Start selling access to your website content. This extension gives you the ability to define list of E-Commerce products that you can bind with any content on your website. The properly configured AAM Payment widget allows any authenticated user to purchase access with credit/debig card or PayPal. Braintree and Stripe gateways are used to handle payment transactions.',
                'url'         => 'https://aamplugin.com/extension/ecommerce',
                'version'     => (defined('AAM_ECOMMERCE') ? constant('AAM_ECOMMERCE') : null),
                'latest'      => '1.2.1'
            ),
            'AAM_MULTISITE' => array(
                'title'       => 'Multisite',
                'id'          => 'AAM_MULTISITE',
                'type'        => 'GNU',
                'license'     => 'AAMMULTISITE',
                'description' => 'Convenient way to navigate between different sites in the Network Admin Panel. This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/multisite-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_MULTISITE') ? constant('AAM_MULTISITE') : null),
                'latest'      => '2.5.4'
            ),
            'AAM_USER_ACTIVITY' => array(
                'title'       => 'User Activities',
                'id'          => 'AAM_USER_ACTIVITY',
                'type'        => 'GNU',
                'license'     => 'AAMUSERACTIVITY',
                'description' => 'Track any kind of user or visitor activity on your website. <a href="https://aamplugin.com/help/how-to-track-any-wordpress-user-activity" target="_blank">Read more.</a> This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/user-activity-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_USER_ACTIVITY') ? constant('AAM_USER_ACTIVITY') : null),
                'latest'      => '1.4.1'
            ),
            'AAM_SOCIAL_LOGIN' => array(
                'title'       => 'Social Login',
                'id'          => 'AAM_SOCIAL_LOGIN',
                'type'        => 'GNU',
                'tag'         => 'ALPHA', 
                'license'     => 'AAMSOCIALLOGIN',
                'description' => 'Login to your website with social networks like Facebook, Twitter, Instagram etc. <a href="https://aamplugin.com/help/how-does-aam-social-login-works" target="_blank">Read more.</a> This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/social-login-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_SOCIAL_LOGIN') ? constant('AAM_SOCIAL_LOGIN') : null),
                'latest'      => '0.2.1'
            ),
        );
    }
}