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
class AAM_Framework_Utility_Capability
{

    /**
     * Determine the max user level based on provided array of capabilities
     *
     * @param array $caps
     *
     * @return int
     *
     * @access public
     * @static
     *
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
     * Get list of all known capabilities
     *
     * This method returns the combined list of all registered capabilities on the
     * role level as well as caps for the currently logged in user.
     *
     * @return array
     *
     * @access public
     * @static
     *
     * @version 7.0.0
     */
    public static function get_all_caps()
    {
        $result = [];

        foreach (wp_roles()->role_objects as $role) {
            if (is_array($role->capabilities)) {
                $result = array_merge($result, array_keys($role->capabilities));
            }
        }

        // Also get the list of all capabilities assigned directly to user
        $user = wp_get_current_user();

        if (is_a($user, WP_User::class)) {
            $result = array_merge($result, array_keys($user->allcaps));
        }

        return array_unique($result);
    }

}