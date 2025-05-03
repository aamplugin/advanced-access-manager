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
 * @property string $type
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Preference_Interface
{

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level();

    /**
     * Get the collection of resource preferences
     *
     * @param string $offset [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_preferences($offset = null);

    /**
     * Set resource preferences
     *
     * @param array  $preferences
     * @param string $offset      [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_preferences(array $preferences, $offset = null);

    /**
     * Check if preferences are customized for current access level
     *
     * @param string $offset [Optional]
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized($offset = null);

    /**
     * Reset all explicitly defined preferences
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function reset();

}