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
    protected $type = AAM_Framework_Type_Resource::USER;

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_User $resource_identifier
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        return $resource_identifier->ID;
    }

     /**
     * @inheritDoc
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        return get_user($id);
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach($this->policies()->statements('User:*') as $stm) {
            $bits = explode(':', $stm['Resource']);

            // If user identifier is not numeric, convert it to WP_User::ID for
            // consistency
            if (is_numeric($bits[1])) {
                $id = intval($bits[1]);
            } else {
                $user = $this->users->get_user($bits[1]);
                $id   = is_object($user) ? $user->ID : null;
            }

            if (!empty($id)) {
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $this->policy->statement_to_permission($stm, $this->type)
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}