<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Misc
{

    /**
     * Confirm that provided value is base64 encoded string
     *
     * @param string $str
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public static function is_base64_encoded($str)
    {
        $result = false;

        // Check if the string is valid base64 by matching with base64 pattern
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) {
            // Decode the string and check if it can be re-encoded to match original
            $decoded = base64_decode($str, true);

            if ($decoded !== false && base64_encode($decoded) === $str) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Determine the max user level based on provided array of capabilities
     *
     * @param array $caps
     *
     * @return int
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function get_max_user_level($caps)
    {
        $max = 0;

        if (is_array($caps)) {
            foreach ($caps as $cap => $granted) {
                if (!empty($granted) && (strpos($cap, 'level_') === 0)) {
                    $level = intval(substr($cap, 6));
                    $max   = ($max < $level ? $level : $max);
                }
            }
        }

        return intval($max);
    }

    /**
     * Checks if provided capability is registered on the site
     *
     * Due to potential performance implications, this method does not check if
     * capability is assigned to any user directly except current user
     *
     * @param string $cap
     *
     * @return boolean
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function capability_exists($cap)
    {
        static $all_caps = [];

        if (empty($all_caps)) {
            foreach (wp_roles()->role_objects as $role) {
                if (is_array($role->capabilities)) {
                    $all_caps = array_merge($all_caps, $role->capabilities);
                }
            }

            // Also get current user's capability add add them to the array
            if (is_user_logged_in()) {
                $user     = wp_get_current_user();
                $all_caps = array_merge($user->allcaps, $all_caps);
            }
        }

        return (is_string($cap) && array_key_exists($cap, $all_caps));
    }

}