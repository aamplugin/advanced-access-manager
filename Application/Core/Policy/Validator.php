<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Composer\Semver\Semver;

/**
 * AAM core policy validator
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since AAM v5.7.3
 */
class AAM_Core_Policy_Validator {
    
    /**
     * Raw policy text
     * 
     * @var string
     * 
     * @access protected 
     */
    protected $policy;
    
    /**
     * Parsed JSON document
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $json;
    
    /**
     * Collection of errors
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $errors = array();
    
    /**
     * Constructor
     * 
     * @param string $policy
     * 
     * @access public
     */
    public function __construct($policy) {
        $this->policy = trim($policy);
        $this->json   = json_decode($policy, true);
    }
    
    /**
     * Validate the policy
     * 
     * @return array
     * 
     * @access public
     */
    public function validate() {
        $steps = array(
            'isJSON',            // #1. Check if policy is valid JSON
            'isNotEmpty',        // #2. Check if policy is not empty
            'isValidDependency', // #3. Check if all dependencies are defined properly
        );
        
        foreach($steps as $step) {
            if (call_user_func(array($this, $step)) === false) {
                break;
            }
        }
        
        return $this->errors;
    }
    
    /**
     * Check if policy is valid JSON
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isJSON() {
        $result = is_array($this->json);
        
        if ($result === false) {
            $this->errors[] = __('The policy is not valid JSON object', AAM_KEY);
        }
        
        return $result;
    }
    
    /**
     * Check if policy is empty
     * 
     * @return boolean
     * 
     * @access public
     */
    public function isNotEmpty() {
        $result = !empty($this->policy) && !empty($this->json);
        
        if ($result === false) {
            $this->errors[] = __('The policy document is empty', AAM_KEY);
        }
        
        return $result;
    }
    
    public function isValidDependency() {
        if (!empty($this->json['Dependency'])) {
            foreach($this->json['Dependency'] as $app => $constraints) {
                try {
                    $satisfies = Semver::satisfies(
                            $this->getAppVersion(strtolower($app)), $constraints
                    );
                    if ($satisfies === false) {
                        throw new Exception(
                            AAM_Backend_View_Helper::preparePhrase(
                                "The dependency [{$app}] does not satisfy version requirement by the policy",
                                'b'
                            )
                        );
                    }
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }
        }
    }
    
    protected function getAppVersion($app) {
        global $wp_version;
        
        if ($app === 'wordpress') {
            $version = $wp_version;
        } else {
            $version = $this->getPluginVersion($app);
        }
        
        return $version;
    }
    
    protected function getPluginVersion($slug) {
        static $plugins = null;
        
        if (is_null($plugins)) {
            if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $plugins = get_plugins();
        }
        
        $version = null;
        
        foreach($plugins as $plugin => $data) {
            if (stripos($plugin, $slug . '/') === 0) {
                $version = $data['Version'];
            }
        }
        
        if (is_null($version)) {
            throw new Exception(
                AAM_Backend_View_Helper::preparePhrase(
                    "The plugin [{$slug}] is required by the policy",
                    'b'
                )
            );
        }
        
        return $version;
    }
}