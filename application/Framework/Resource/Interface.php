<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for all resources that hold settings
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_Interface
{

    /**
     * Resource type
     *
     * Resource type is just an alias that refers to the instance of resource. It is
     * used to better understand how to work with resource and merge settings
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
     * Get the collection of resource settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_settings($explicit_only = false);

    /**
     * Set the collection of resource settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function set_settings(array $settings, $explicit_only = false);

    /**
     * Merge incoming settings with resource settings
     *
     * @param array $incoming_settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_settings($incoming_settings);

    /**
     * Check if resource settings are overwritten
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_overwritten();

    /**
     * Reset all explicitly defined settings to default
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset();

}