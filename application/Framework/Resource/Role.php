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
    protected $type = AAM_Framework_Type_Resource::ROLE;

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
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        return wp_roles()->get_role($id);
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach($this->policies()->statements('Role:*') as $stm) {
            $bits = explode(':', $stm['Resource']);
            $id   = $bits[1];

            if (count($bits) === 2) { // Role:<slug>
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $this->policy->statement_to_permission($stm, $this->type)
                );
            } elseif (count($bits) === 3 && $bits[2] === 'users') {
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $this->policy->statement_to_permission($stm, 'user')
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}