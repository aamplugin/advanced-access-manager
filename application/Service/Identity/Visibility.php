<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & roles visibility controller
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Identity_Visibility
{

    /**
     * Sudo resource for identity visibility
     *
     * @version 7.0.0
     */
    const TYPE = 'identity_visibility';

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
        $this->_internal_id  = $internal_id;

        // Read both role & user permissions and only extract list permission
        $settings = AAM::api()->settings([ 'access_level' => $access_level ]);
        $controls = $settings->get_setting($internal_id, []);

        $this->_permissions = [];

        foreach($controls as $id => $perms) {
            $filtered = array_filter($perms, function($k) {
                return in_array($k, [ 'list_users', 'list_user' ], true);
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($filtered)) {
                $this->_permissions[$id] = $filtered;
            }
        }
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