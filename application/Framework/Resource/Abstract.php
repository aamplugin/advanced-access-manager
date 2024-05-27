<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract class that represents AAM resource concept
 *
 * AAM Resource is a website resource that you manage access to for users, roles or
 * visitors. For example, it can be any website post, page, term, backend menu etc.
 *
 * On another hand, AAM Resource is a “container” with specific settings for any user,
 * role or visitor. For example login, logout redirect, default category or access
 * denied redirect rules.
 *
 * @package AAM
 * @version 7.0.0
 */
abstract class AAM_Framework_Resource_Abstract
{

    /**
     * AAM core resource slug
     *
     * The slug is a unique resource identifier  (e.g. menu, post)
     *
     * @version 7.0.0
     */
    const TYPE = 'abstract';

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Abstract
     *
     * @access private
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Resource internal identifier
     *
     * Some resource may have unique identifier like each post or term has unique
     * auto-incremented ID, or post type - unique slug. Other resource, like menu,
     * toolbar, do not have unique.
     *
     * @var int|string|null
     *
     * @access private
     * @version 7.0.0
     */
    private $_internal_id = null;

    /**
     * Resource settings
     *
     * Array of access controls or settings.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_settings = array();

    /**
     * Explicit settings (not inherited from parent access level)
     *
     * When resource is initialized, it already contains the final set of the settings,
     * inherited from the parent access levels. This properly contains access settings
     * that are explicitly defined for current resource.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_explicit_settings = array();

    /**
     * Overwritten indicator
     *
     * If settings for specific resource were detected before inheritance mechanism
     * kicked off, then it is considered overwritten
     *
     * @var boolean
     *
     * @access private
     * @version 7.0.0
     */
    private $_is_overwritten = false;

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Abstract $access_level
     * @param mixed                              $id
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct($access_level, $internal_id)
    {
        $this->_access_level = $access_level;
        $this->_internal_id  = $internal_id;

        $this->initialize();
    }

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
    public function get_internal_id()
    {
        return $this->_internal_id;
    }

    /**
     * Get the collection of resource settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_settings()
    {
        return $this->_settings;
    }

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
    public function set_settings(array $settings)
    {
        $this->_settings = $settings;
    }

    /**
     * Check if settings are overwritten for this resource
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_overwritten()
    {
        return $this->_is_overwritten;
    }

    public function merge_settings($incoming_settings)
    {

    }

    /**
     * Initialize resource
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    abstract protected function initialize();

}