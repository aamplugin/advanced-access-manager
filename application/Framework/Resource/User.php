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
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_User::class)) {
            $result = $resource_identifier;
        } elseif (is_a($resource_identifier, AAM_Framework_Proxy_User::class)) {
            $result = $resource_identifier->get_core_instance();
        } else {
            $user = AAM_Framework_Manager::_()->users->get_user(
                $resource_identifier
            );

            if (is_a($user, AAM_Framework_Proxy_User::class)) {
                $result = $user->get_core_instance();
            }
        }

        if (!is_a($result, WP_User::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

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
     */
    private function _apply_policy()
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
                $user = $manager->users->get_user($bits[1]);
                $id   = is_object($user) ? $user->ID : null;
            }

            if (!empty($id)) {
                $result[$id] = array_replace(
                    isset($result[$id]) ? $result[$id] : [],
                    $manager->policy->statement_to_permission($stm, self::TYPE)
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}