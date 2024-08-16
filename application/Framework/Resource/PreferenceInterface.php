<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for all resources that are classified as such that hold preferences
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_PreferenceInterface
{

    /**
     * Get the collection of resource preferences
     *
     * @param boolean $explicit_only
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_preferences($explicit_only = false);

    /**
     * Set resource preferences
     *
     * @param array $preferences
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function set_preferences(array $preferences);

    /**
     * Get a specific preference
     *
     * @param string $preference_key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_preference($preference_key, $default = null);

    /**
     * Set explicit permission
     *
     * @param string $preference_key
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_preference($preference_key, $value);

    /**
     * Merge incoming preferences
     *
     * @param array $incoming_preferences
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_preferences($incoming_preferences);

}