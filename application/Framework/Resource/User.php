<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_User implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::USER;

    /**
     * Initialize the resource
     *
     * @param mixed $resource_identifier
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        if (is_a($resource_identifier, WP_User::class)) {
            $this->_internal_id   = $resource_identifier->ID;
            $this->_core_instance = AAM_Framework_Manager::_()->users->user(
                $resource_identifier
            );
        } elseif (is_a($resource_identifier, AAM_Framework_Proxy_User::class)) {
            $this->_core_instance = $resource_identifier;
            $this->_internal_id   = $resource_identifier->ID;
        } else {
            $user = AAM_Framework_Manager::_()->users->user($resource_identifier);

            if (is_a($user, AAM_Framework_Proxy_User::class)) {
                $this->_core_instance = $user;
                $this->_internal_id   = $user->ID;
            } elseif (is_scalar($resource_identifier)) {
                $this->_internal_id = $resource_identifier;
            }
        }

        if (empty($this->_internal_id)) {
            throw new OutOfRangeException('The user resource identifier is invalid');
        }
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource User. However, it is possible
        // that User resource is initialized with non-existing user ID. Example of
        // such would be a wildcard
        if (is_a($this->_core_instance, AAM_Framework_Proxy_User::class)) {
            $manager = AAM_Framework_Manager::_();
            $service = $manager->policies($this->get_access_level());

            $list = array_merge(
                $service->statements('User:' . $this->ID),
                $service->statements('User:' . $this->user_login),
                $service->statements('User:' . $this->user_email)
            );

            foreach($list as $stm) {
                $permissions = array_replace(
                    $manager->policy->statement_to_permission($stm, 'user'),
                    $permissions
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}