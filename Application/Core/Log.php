<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Log
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Log {

    /**
     * Add new warning
     * 
     * @param string $message
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function add($message) {
        $basedir = WP_CONTENT_DIR . '/aam/logs';
        $ok      = file_exists($basedir);
        
        if (!$ok) {
            $ok = @mkdir($basedir, fileperms( ABSPATH ) & 0777 | 0755, true);
        }

        if ($ok) {
            $ok = error_log(
                '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", 
                3, 
                $basedir . '/aam.log'
            );
        }

        return $ok;
    }

}