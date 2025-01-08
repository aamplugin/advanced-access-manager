<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role resource class
 *
 * @property string  $slug
 * @property string  $display_name
 * @property WP_Role $role
 * @property int     $user_count
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Role
implements
    AAM_Framework_Resource_Interface,
    ArrayAccess,
    AAM_Framework_Resource_AggregateInterface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::ROLE;

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
        if (!empty($resource_identifier)) {
            if (is_a($resource_identifier, WP_Role::class)) {
                $this->_internal_id   = $resource_identifier->name;
                $this->_core_instance = AAM_Framework_Manager::_()->roles->get_role(
                    $resource_identifier->name
                );
            } elseif (is_a($resource_identifier, AAM_Framework_Proxy_Role::class)) {
                $this->_core_instance = $resource_identifier;
                $this->_internal_id   = $resource_identifier->slug;
            } elseif (is_string($resource_identifier)) {
                $roles = AAM_Framework_Manager::_()->roles;

                if ($roles->is_role($resource_identifier)) {
                    $this->_core_instance = $roles->role($resource_identifier);
                    $this->_internal_id   = $this->_core_instance->slug;
                } else {
                    $this->_internal_id = $resource_identifier;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        if (empty($this->_internal_id)) { // Assume aggregation
            foreach($service->statements('Role:*') as $stm) {
                $bits        = explode(':', $stm['Resource']);
                $id          = $bits[1];
                $result[$id] = isset($result[$id]) ? $result[$id] : [];

                $result[$id] = array_replace(
                    $result[$id],
                    $manager->policy->statement_to_permission($stm, self::TYPE)
                );
            }
        } elseif (is_a($this->_core_instance, AAM_Framework_Proxy_Role::class)) {
            $list = array_merge(
                $service->statements('Role:' . $this->_internal_id),
                $service->statements('Role:' . $this->_internal_id . ':users')
            );

            foreach($list as $stm) {
                $permissions = array_replace(
                    $manager->policy->statement_to_permission($stm, self::TYPE),
                    $permissions
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}