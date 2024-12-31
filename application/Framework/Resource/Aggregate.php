<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Aggregated collection of permissions for all resources of given type
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Aggregate
{

    /**
     * Resource type that is used to aggregate permissions of all resources of a
     * given type
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::AGGREGATE;

    /**
     * Reference to the access level
     *
     * @var AAM_Framework_AccessLevel_Interface
     *
     * @access private
     * @version 7.0.0
     */
    private $_access_level = null;

    /**
     * Internal resource identifier
     *
     * @var string
     *
     * @access private
     * @version 7.0.0
     */
    private $_internal_id = null;

    /**
     * Resource permissions
     *
     * Array of final permissions. The final permissions are those that have been
     * properly inherited and merged.
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_permissions = [];

    /**
     * Constructor
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param string                              $internal_id
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $internal_id
    ) {
        $this->_access_level = $access_level;
        $this->_internal_id = $internal_id;

        // Read all the permissions for a given resource type
        $this->_permissions = AAM::api()->settings(
            [ 'access_level' => $access_level ]
        )->get_setting($internal_id, []);
    }

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * @inheritDoc
     */
    public function get_internal_id()
    {
        return $this->_internal_id;
    }

    /**
     * @inheritDoc
     */
    public function get_permissions()
    {
        return $this->_permissions;
    }

    /**
     * @inheritDoc
     */
    public function set_permissions($permissions)
    {
        $this->_permissions = $permissions;
    }

}