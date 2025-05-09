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
class AAM_Framework_Utility_Capabilities implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Determine the max user level based on provided array of capabilities
     *
     * @param array $caps
     *
     * @return int
     * @access public
     *
     * @version 7.0.0
     */
    public function get_max_user_level($caps)
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
     * @param WP_User|int $user [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_all_caps($user = null)
    {
        $result = [];

        foreach (wp_roles()->role_objects as $role) {
            if (is_array($role->capabilities)) {
                $result = array_merge($result, array_keys($role->capabilities));
            }
        }

        // Also get the list of all capabilities assigned directly to user
        if (is_numeric($user)) {
            $user = get_user_by('id', $user);
        }

        if (is_a($user, WP_User::class)) {
            $result = array_merge($result, array_keys($user->allcaps));
        }

        return array_unique($result);
    }

    /**
     * Check if capability exists
     *
     * @param string $capability
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function exists($capability)
    {
        return in_array($capability, $this->get_all_caps(), true);
    }

}