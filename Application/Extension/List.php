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
                'description' => 'Get the complete list of all premium AAM extensions in one package and all future premium extensions already included for now additional cost.',
                'url'         => 'https://aamplugin.com/complete-package',
                'version'     => (defined('AAM_COMPLETE_PACKAGE') ? constant('AAM_COMPLETE_PACKAGE') : null),
                'latest'      => '3.8.21'
            ),
            'AAM_PLUS_PACKAGE' => array(
                'title'       => 'Plus Package',
                'id'          => 'AAM_PLUS_PACKAGE',
                'type'        => 'commercial',
                'description' => 'Manage access to your WordPress website posts, pages, media, custom post types, categories and hierarchical taxonomies for any role, individual user, visitors or even define default access for everybody; and do this separately for frontend, backend or API levels. As the bonus, define more granular access to how comments can be managed on the backend by other users.',
                'url'         => 'https://aamplugin.com/extension/plus-package',
                'version'     => (defined('AAM_PLUS_PACKAGE') ? constant('AAM_PLUS_PACKAGE') : null),
                'latest'      => '3.10'
            ),
            'AAM_IP_CHECK' => array(
                'title'       => 'IP Check',
                'id'          => 'AAM_IP_CHECK',
                'type'        => 'commercial',
                'description' => 'Manage access to your WordPress website by visitor\'s IP address and referred hosts or completely lockdown the entire website and allow only certain IP ranges.',
                'url'         => 'https://aamplugin.com/extension/ip-check',
                'version'     => (defined('AAM_IP_CHECK') ? constant('AAM_IP_CHECK') : null),
                'latest'      => '2.0.1'
            ),
            'AAM_ROLE_HIERARCHY' => array(
                'title'       => 'Role Hierarchy',
                'id'          => 'AAM_ROLE_HIERARCHY',
                'type'        => 'commercial',
                'description' => 'Define and manage complex WordPress role hierarchy where child role inherits all access settings from its parent with ability to override setting for any specific role.',
                'url'         => 'https://aamplugin.com/extension/role-hierarchy',
                'version'     => (defined('AAM_ROLE_HIERARCHY') ? constant('AAM_ROLE_HIERARCHY') : null),
                'latest'      => '1.4.1'
            ),
            'AAM_ECOMMERCE' => array(
                'title'       => 'E-Commerce',
                'id'          => 'AAM_ECOMMERCE',
                'type'        => 'commercial',
                'new'         => true,
                'description' => 'Start monetizing access to your premium content. Restrict access to read any WordPress post, page or custom post type until user purchase access to it.',
                'url'         => 'https://aamplugin.com/extension/ecommerce',
                'version'     => (defined('AAM_ECOMMERCE') ? constant('AAM_ECOMMERCE') : null),
                'latest'      => '1.2.3'
            ),
            'AAM_MULTISITE' => array(
                'title'       => 'Multisite',
                'id'          => 'AAM_MULTISITE',
                'type'        => 'GNU',
                'license'     => 'AAMMULTISITE',
                'description' => 'Convenient way to navigate between different sites in the Network Admin Panel. This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/multisite-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_MULTISITE') ? constant('AAM_MULTISITE') : null),
                'latest'      => '2.5.5'
            ),
            'AAM_USER_ACTIVITY' => array(
                'title'       => 'User Activities',
                'id'          => 'AAM_USER_ACTIVITY',
                'type'        => 'GNU',
                'license'     => 'AAMUSERACTIVITY',
                'description' => 'Track any kind of user or visitor activity on your website. <a href="https://aamplugin.com/article/how-to-track-any-wordpress-user-activity" target="_blank">Read more.</a> This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/user-activity-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_USER_ACTIVITY') ? constant('AAM_USER_ACTIVITY') : null),
                'latest'      => '1.4.2'
            ),
            'AAM_SOCIAL_LOGIN' => array(
                'title'       => 'Social Login',
                'id'          => 'AAM_SOCIAL_LOGIN',
                'type'        => 'GNU',
                'tag'         => 'ALPHA', 
                'license'     => 'AAMSOCIALLOGIN',
                'description' => 'Login to your website with social networks like Facebook, Twitter, Instagram etc. <a href="https://aamplugin.com/article/how-does-aam-social-login-works" target="_blank">Read more.</a> This is the open source solution and you can find it on the <a href="https://github.com/aamplugin/social-login-extension" target="_blank">Github here</a>.',
                'version'     => (defined('AAM_SOCIAL_LOGIN') ? constant('AAM_SOCIAL_LOGIN') : null),
                'latest'      => '0.2.1'
            ),
        );
    }
}