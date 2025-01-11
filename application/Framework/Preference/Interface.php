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
interface AAM_Framework_Preference_Interface
{

    /**
     * Preference type
     *
     * @var string
     *
     * @version 7.0.0
     */
    const TYPE = null;

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get_access_level();

    /**
     * Get the collection of resource preferences
     *
     * @param bool $explicit [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get($explicit = false);

    /**
     * Set resource preferences
     *
     * @param array $preferences
     * @param bool  $explicit    [Optional]
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function set(array $preferences, $explicit = true);

    /**
     * Check if preferences are customized for current access level
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_customized();

    /**
     * Reset all explicitly defined preferences
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset();

}