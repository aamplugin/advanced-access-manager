<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Core compatibility with older versions
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @todo   Remove Feb 2018
 */
class AAM_Core_Compatibility {
    
    /**
     * 
     */
    public static function checkConfigPressCompatibility($key) {
        if (strpos($key, 'htpasswd') === 0) {
            $key = str_replace('htpasswd', 'feature.metabox.htpasswd', $key);
        } elseif (strpos($key, 'export') === 0) {
            $key = str_replace('export', 'feature.export', $key);
        } elseif (strpos($key, 'default.category') === 0) {
            $key = str_replace('default.category', 'feature.post.defaultTerm', $key);
        } elseif (strpos($key, 'extention') === 0) {
            $key = str_replace('extention', 'core.extention', $key);
        } elseif (strpos($key, 'login') === 0) {
            $key = str_replace('login', 'feature.secureLogin', $key);
        }
        
        return $key;
    }
    
    /**
     * Convert all-style AAM settings to standard ConfigPress style settings
     * 
     * @param array $config
     * 
     * @return array
     * @since  AAM 5.3.1
     * @todo   Remove June 1st 2019
     */
    public static function normalizeConfigOptions($config) {
        if (is_array($config)) {
            $changes = 0;
            $changes += self::normalizeOption('manage-capability', 'core.settings.editCapabilities', $config);
            $changes += self::normalizeOption('backend-access-control', 'core.settings.backendAccessControl', $config);
            $changes += self::normalizeOption('frontend-access-control', 'core.settings.frontendAccessControl', $config);
            $changes += self::normalizeOption('api-access-control', 'core.settings.apiAccessControl', $config);
            $changes += self::normalizeOption('render-access-metabox', 'ui.settings.renderAccessMetabox', $config);
            $changes += self::normalizeOption('show-access-link', 'ui.settings.renderAccessActionLink', $config);
            $changes += self::normalizeOption('secure-login', 'core.settings.secureLogin', $config);
            $changes += self::normalizeOption('core.xmlrpc', 'core.settings.xmlrpc', $config);
            $changes += self::normalizeOption('core.restful', 'core.settings.restful', $config);
            $changes += self::normalizeOption('jwt-authentication', 'core.settings.jwtAuthentication', $config);
            $changes += self::normalizeOption('ms-member-access', 'core.settings.multisiteMemberAccessControl', $config);
            $changes += self::normalizeOption('media-access-control', 'core.settings.mediaAccessControl', $config);
            $changes += self::normalizeOption('manage-hidden-post-types', 'core.settings.manageHiddenPostTypes', $config);
            $changes += self::normalizeOption('page-category', 'core.settings.pageCategory', $config);
            $changes += self::normalizeOption('media-category', 'core.settings.mediaCategory', $config);
            $changes += self::normalizeOption('multi-category', 'core.settings.multiCategory', $config);
            $changes += self::normalizeOption('login-timeout', 'core.settings.loginTimeout', $config);
            $changes += self::normalizeOption('single-session', 'core.settings.singleSession', $config);
            $changes += self::normalizeOption('brute-force-lockout', 'core.settings.bruteForceLockout', $config);
            $changes += self::normalizeOption('inherit-parent-post', 'core.settings.inheritParentPost', $config);
            //$changes += self::normalizeOption('', '', $config);
            
            if ($changes > 0) {
                if (is_multisite()) {
                    $result = AAM_Core_API::updateOption('aam-utilities', $config, 'site');
                } else {
                    $result = AAM_Core_API::updateOption('aam-utilities', $config);
                }
            }
        }
        
        return $config;
    }
    
    /**
     * 
     * @param type $option
     * @param type $normalizedName
     * @param type $config
     * @return int
     */
    protected static function normalizeOption($option, $normalizedName, &$config) {
        $changed = 0;
        
        if (array_key_exists($option, $config)) {
            $value = $config[$option];
            unset($config[$option]);
            $config[$normalizedName] = $value;
            $changed = 1;
        }
        
        return $changed;
    }
    
    /**
     * Get config
     * @return type
     */
    public static function getConfig() {
        $config = AAM_Core_API::getOption('aam-utilities', array(), 'site');
        
        foreach(array_keys((is_array($config) ? $config : array())) as $option) {
            if (strpos($option, 'frontend.redirect') !== false) {
                self::convertConfigOption('redirect', $config, $option);
            } elseif (strpos($option, 'backend.redirect') !== false) {
                self::convertConfigOption('redirect', $config, $option);
            } elseif (strpos($option, 'login.redirect') !== false) {
                self::convertConfigOption('loginRedirect', $config, $option);
            } elseif (strpos($option, 'frontend.teaser') !== false) {
                self::convertConfigOption('teaser', $config, $option);
            }
        }
        
        return self::normalizeConfigOptions($config);
    }
    
    /**
     * 
     */
    public static function initExtensions() {
        //block deprecated extensions from loading
        define('AAM_UTILITIES', '99');
        define('AAM_ROLE_FILTER', '99');
        define('AAM_POST_FILTER', '99');
        define('AAM_REDIRECT', '99');
        define('AAM_CONTENT_TEASER', '99');
        define('AAM_LOGIN_REDIRECT', '99');
        define('AAM_CONFIGPRESS', '99');
        //TODO - Remove this in Jul 2019
        
        //utilities option
        add_filter('aam-utility-property', 'AAM_Core_Config::get', 10, 2);
    }
    
    /**
     * 
     * @return type
     */
    public static function getLicenseList() {
        $list = AAM_Core_API::getOption('aam-extensions', array(), 'site');
        
        if (empty($list)) {
            $list = AAM_Core_API::getOption('aam-extension-license', array(), 'site');
            if (!empty($list)) {
                $converted = array();
                
                foreach($list as $title => $license) {
                    $id             = strtoupper(str_replace(' ', '_', $title));
                    $converted[$id] = array('license' => $license);
                }
                
                AAM_Core_API::updateOption('aam-extensions', $converted);
                AAM_Core_API::deleteOption('aam-extension-license');
            }
        }
        
        return $list;
    }
    
    /**
     * 
     * @staticvar type $subject
     * @param type $oid
     * @param type &$config
     * @param type $option
     * 
     * @todo Legacy remove Jul 2018
     */
    protected static function convertConfigOption($oid, &$config, $option) {
        static $subject = null;
        
        if (is_null($subject)) {
            $subject = new AAM_Core_Subject_Default;
        }
        
        $object = $subject->getObject($oid);
        
        if (is_a($object, 'AAM_Core_Subject')) {
            $object->save($option, $config[$option]);
            unset($config[$option]);
            AAM_Core_API::updateOption('aam-utilities', $config);
        }
    }

}