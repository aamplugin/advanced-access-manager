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
     * @param string                              $resource_type
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function __construct(
        AAM_Framework_AccessLevel_Interface $access_level, $resource_type
    ) {
        $this->_access_level = $access_level;
        $this->_internal_id = $resource_type;

        // Read all the permissions for a given resource type
        $permissions = AAM::api()->settings(
            $access_level
        )->get_setting($resource_type, []);

        // JSON Access Policy is deeply embedded in the framework, thus take it into
        // consideration during resource initialization
        if (AAM_Framework_Manager::_()->config->get('service.policies.enabled', true)) {
            $this->_permissions = $this->_apply_policy($permissions);
        } else {
            $this->_permissions = $permissions;
        }
    }

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level()
    {
        return $this->_access_level;
    }

    /**
     * Get aggregated resource type
     *
     * @return string
     * @access public
     *
     * @version 7.0.0
     */
    public function get_internal_id()
    {
        return $this->_internal_id;
    }

    /**
     * Get complete array of aggregated permissions
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permissions()
    {
        return $this->_permissions;
    }

    /**
     * Set complete array of aggregated permissions
     *
     * @param array $permissions
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permissions($permissions)
    {
        $this->_permissions = $permissions;
    }

    /**
     * Apply permissions extracted from policies
     *
     * @param array $permissions
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _apply_policy($permissions)
    {
        $resource_type = $this->get_internal_id();

        if ($resource_type === AAM_Framework_Type_Resource::USER) {
            $aggregated = $this->_aggregate_user_policy_resources();
        } else {
            $aggregated = [];
        }

        return apply_filters(
            'aam_apply_policy_filter',
            array_replace($aggregated, $permissions),
            $this
        );
    }

    /**
     * Aggregate User resources
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _aggregate_user_policy_resources()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        foreach($service->statements('User:*') as $stm) {
            $bits = explode(':', $stm['Resource']);

            // If user identifier is not numeric, convert it to WP_User::ID for
            // consistency
            if (is_numeric($bits[1])) {
                $id = intval($bits[1]);
            } else {
                $user = $manager->users->user($bits[1]);
                $id   = is_object($user) ? $user->ID : null;
            }

            if (!empty($id)) {
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $manager->policy->statement_to_permission(
                        $stm, $this->get_internal_id()
                    )
                );
            }
        }

        return $result;
    }

}