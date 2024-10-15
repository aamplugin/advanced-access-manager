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

}