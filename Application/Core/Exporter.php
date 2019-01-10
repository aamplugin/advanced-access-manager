<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Exporter
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Exporter {
    
    /**
     *
     * @var type 
     */
    protected $config = array();

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $blog = null;
    
    /**
     *
     * @var type 
     */
    protected $output = array();
    
    /**
     *
     * @var type 
     */
    protected $cache = array();
    
    /**
     * 
     * @param type $config
     */
    public function __construct($config, $blog = null) {
        $this->config = $config;
        $this->blog   = ($blog ? $blog : get_current_blog_id());
    }
    
    /**
     * 
     * @return type
     */
    public function run() {
        $this->output = array(
            'version'  => AAM_Core_API::version(),
            'plugin'   => AAM_KEY,
            'datetime' => date('Y-m-d H:i:s'),
            'metadata' => $this->config,
            'dataset'  => array()
        );
        
        foreach($this->config as $backet => $features) {
            $method = 'export' . ucfirst($backet);
            
            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), explode(',', $features));
            } else {
                $this->output = apply_filters(
                    'aam-export-filter', $this->output, $backet, $this->config
                );
            }
        }
        
        return $this->output;
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $features
     */
    protected function exportSystem($features) {
        global $wpdb;

        foreach($features as $feature) {
            if ($feature === 'roles') {
                $this->add(
                    '_user_roles',
                    AAM_Core_API::getOption(
                        $wpdb->get_blog_prefix($this->blog) . 'user_roles',
                        array(),
                        $this->blog
                    )
                );
            } elseif (in_array($feature, array('utilities', 'settings'), true)) {
                $this->add(
                    AAM_Core_Config::OPTION, 
                    AAM_Core_API::getOption(AAM_Core_Config::OPTION, '', 'site')
                );
            } elseif ($feature === 'configpress') {
                $this->add(
                    'aam-configpress', AAM_Core_ConfigPress::getInstance()->read()
                );
            } else {
                do_action('aam-export-action', 'system', $feature, $this);
            }
        }
    }
    
    /**
     * 
     * @param type $features
     */
    protected function exportRoles($features) {
        foreach($features as $feature) {
            if ($feature === 'menu') {
                $this->pushData('options', '/^aam_menu_role/');
            } elseif ($feature === 'toolbar') {
                $this->pushData('options', '/^aam_toolbar_role/');
            } elseif ($feature === 'uri') {
                $this->pushData('options', '/^aam_uri_role/');
            } elseif ($feature === 'route') {
                $this->pushData('options', '/^aam_route_role/');
            } elseif ($feature === 'metabox') {
                $this->pushData('options', '/^aam_metabox_role/');
            } elseif ($feature === 'post') {
                $this->pushData('options', '/^aam_type_[\w_\-]+_role/');
                $this->pushData('options', '/^aam_term_[\d]+\|.+_role/');
                $this->pushData('postmeta', '/^aam-post-access-role/');
            } elseif ($feature === 'redirect') {
                $this->pushData('options', '/^aam_redirect_role/');
                $this->pushData('options', '/^aam_loginredirect_role/');
                $this->pushData('options', '/^aam_logoutredirect_role/');
            }
        }
    }
    
    /**
     * 
     * @param type $features
     */
    protected function exportUsers($features) {
        global $wpdb;
        
        foreach($features as $feature) {
            if ($feature === 'menu') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_menu/');
            } elseif ($feature === 'toolbar') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_toolbar/');
            } elseif ($feature === 'uri') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_uri/');
            } elseif ($feature === 'route') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_route/');
            } elseif ($feature === 'metabox') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_metabox/');
            } elseif ($feature === 'post') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_type/');
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_term/');
                $this->pushData('postmeta', '/^aam-post-access-user/');
            } elseif ($feature === 'redirect') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_redirect/');
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_loginredirect/');
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_logoutredirect/');
            } elseif ($feature === 'capability') {
                $this->pushData('usermeta', '/^' . $wpdb->prefix . 'aam_capability/');
            }
        }
    }
    
    /**
     * 
     * @param type $features
     */
    protected function exportVisitor($features) {
        foreach($features as $feature) {
            if ($feature === 'metabox') {
                $this->pushData('options', '/^aam_visitor_metabox/');
            } elseif ($feature === 'post') {
                $this->pushData('options', '/^aam_visitor_type/');
                $this->pushData('options', '/^aam_visitor_term/');
                $this->pushData('postmeta', '/^aam-post-access-visitor/');
            } elseif ($feature === 'redirect') {
                $this->pushData('options', '/^aam_visitor_redirect/');
            } elseif ($feature === 'route') {
                $this->pushData('options', '/^aam_visitor_route/');
            } elseif ($feature === 'uri') {
                $this->pushData('options', '/^aam_visitor_uri/');
            }
        }
    }
    
    /**
     * 
     * @param type $features
     */
    protected function exportDefault($features) {
        foreach($features as $feature) {
            if ($feature === 'menu') {
                $this->pushData('options', '/^aam_menu_default/');
            } elseif ($feature === 'metabox') {
                $this->pushData('options', '/^aam_metabox_default/');
            } elseif ($feature === 'route') {
                $this->pushData('options', '/^aam_route_default/');
            } elseif ($feature === 'toolbar') {
                $this->pushData('options', '/^aam_toolbar_default/');
            } elseif ($feature === 'uri') {
                $this->pushData('options', '/^aam_uri_default/');
            } elseif ($feature === 'post') {
                $this->pushData('options', '/^aam_type_[\w_\-]_default/');
                $this->pushData('options', '/^aam_term_[\d]+\|.+_default/');
                $this->pushData('postmeta', '/^aam-post-access-default/');
            } elseif ($feature === 'redirect') {
                $this->pushData('options', '/^aam_redirect_default/');
                $this->pushData('options', '/^aam_loginredirect_default/');
                $this->pushData('options', '/^aam_logoutredirect_default/');
            }
        }
    }
    
    /**
     * 
     * @param type $group
     * @param type $regexp
     */
    public function pushData($group, $regexp) {
        $cache = $this->getCache();
        
        if (is_array($cache[$group])) {
            foreach($cache[$group] as $option) {
                if (isset($option->user_id)) {
                    $id = $option->user_id;
                } elseif (isset($option->post_id)) {
                    $id = $option->post_id;
                } else {
                    $id = null;
                }
                
                if (isset($option->option_name)) {
                    if (preg_match($regexp, $option->option_name)) {
                        $this->add(
                            $this->stripPrefix($option->option_name), 
                            maybe_unserialize($option->option_value), 
                            '_' . $group,
                            $id
                        );
                    }
                } elseif (isset($option->meta_key)) {
                    if (preg_match($regexp, $option->meta_key)) {
                        $this->add(
                            $this->stripPrefix($option->meta_key),
                            maybe_unserialize($option->meta_value), 
                            '_' . $group,
                            $id
                        );
                    }
                }
            }
        }
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $key
     * @return type
     */
    public function stripPrefix($key) {
        global $wpdb;
        
        return preg_replace('/^' . $wpdb->prefix . '/', '_', $key);
    }
    
    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $group
     */
    public function add($key, $value, $group = '_options', $id = null) {
        $compressed = base64_encode(json_encode($value));
        
        if (is_null($id)) { 
            $this->output['dataset'][$group][$key] = $compressed;
        } else {
            $this->output['dataset'][$group][$id][$key] = $compressed;
        }
    }
    
    /**
     * 
     * @global type $wpdb
     * @return type
     */
    protected function getCache() {
        global $wpdb;
        
        if (empty($this->cache)) {
            if (is_multisite()) {
                switch_to_blog(get_main_site_id());
            }
            
            $query  = "SELECT option_name, option_value FROM {$wpdb->options} ";
            $query .= "WHERE option_name LIKE 'aam%'";
            
            $this->cache['options'] = $wpdb->get_results($query);
            
            $query  = "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} ";
            $query .= "WHERE meta_key LIKE '{$wpdb->prefix}aam%'";
            
            $this->cache['usermeta'] = $wpdb->get_results($query);
            
            $query  = "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} ";
            $query .= "WHERE meta_key LIKE 'aam%'";
            
            $this->cache['postmeta'] = $wpdb->get_results($query);
            
            if (is_multisite()) {
                restore_current_blog();
            }
        }
        
        return $this->cache;
    }
    
}