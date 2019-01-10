<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend view helper
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_View_Helper {

    /**
     * Prepare phrase or label
     * 
     * @param string $phrase
     * @param mixed  $...
     * 
     * @return string
     * 
     * @access protected
     */
    public static function preparePhrase($phrase) {
        //prepare search patterns
        $num    = func_num_args();
        $search = ($num > 1 ? array_fill(0, ($num - 1) * 2, null) : array());
        
        array_walk($search, 'AAM_Backend_View_Helper::prepareWalk');

        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }

        //localize the phase first
        return preg_replace($search, $replace, __($phrase, AAM_KEY), 1);
    }
    
    /**
     * 
     * @param string $value
     * @param type $index
     */
    public static function prepareWalk(&$value, $index) {
        $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
    }
    
    /**
     * Get default Access Policy
     * 
     * @global string $wp_version
     * 
     * @return string
     * 
     * @access public
     * @static
     * @since  v5.7.3
     */
    public static function getDefaultPolicy() {
        global $wp_version;
        
        $aamVersion = AAM_Core_API::version();
        
        return <<<EOT
{
    "Version": "1.0.0",
    "Dependency": {
        "wordpress": ">=$wp_version",
        "advanced-access-manager": ">=$aamVersion"
    },
    "Statement": [
        {
            "Effect": "deny",
            "Resource": [],
            "Action": []
        }
    ]
}
EOT;
    }
    
}