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
     * Get resource internal ID
     *
     * The internal ID represents unique resource identify AAM Framework users to
     * distinguish between collection of initialize resources
     *
     * @param bool $serialize
     *
     * @return string|int|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_internal_id($serialize = true);

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
     * Get setting by namespace
     *
     * @param string $ns
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_setting($ns, $default = null);

    /**
     * Set one explicit setting
     *
     * @param string $ns
     * @param mixed  $value
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_explicit_setting($ns, $value);

    /**
     * Check if settings are overwritten for give namespace
     *
     * @param string $ns
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_overwritten($ns = null);

    /**
     * Merge incoming settings
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