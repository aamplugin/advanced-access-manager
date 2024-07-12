<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for all resources
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
     * Get resource internal ID
     *
     * The internal ID represents unique resource identify AAM Framework users to
     * distinguish between collection of initialize resources
     *
     * @return string|int|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_internal_id();

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
    public function get_settings();

    /**
     * Set resource settings
     *
     * @param array $settings
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function set_settings(array $settings);

    /**
     * Get explicitly defined settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_explicit_settings();

    /**
     * Get setting by key
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_setting($key, $default = null);

    /**
     * Set one explicit setting
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_explicit_setting($key, $value);

    /**
     * Set explicit settings
     *
     * @param array $settings
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_explicit_settings(array $settings);

    /**
     * Check if settings are overwritten for this resource
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_overwritten();

    /**
     * Merge incoming settings
     *
     * Depending on the resource type, different strategies may be applied to merge
     * settings
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
     * Reset all explicitly defined settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset();

}