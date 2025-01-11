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
class AAM_Framework_Resource_Role implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::ROLE;

    /**
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Role::class)) {
            $result = $resource_identifier;
        } elseif (is_a($resource_identifier, AAM_Framework_Proxy_Role::class)) {
            $result = $resource_identifier->get_core_instance();
        } elseif (is_string($resource_identifier)) {
            $result = wp_roles()->get_role($resource_identifier);
        }

        if (!is_a($result, WP_Role::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Role $resource_identifier
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        return $resource_identifier->name;
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        foreach($service->statements('Role:*') as $stm) {
            $bits = explode(':', $stm['Resource']);
            $id   = $bits[1];

            if (count($bits) === 2) { // Role:<slug>
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $manager->policy->statement_to_permission($stm, self::TYPE)
                );
            } elseif (count($bits) === 3 && $bits[2] === 'users') {
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $manager->policy->statement_to_permission($stm, 'user')
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}