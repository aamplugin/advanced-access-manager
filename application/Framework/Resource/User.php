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
class AAM_Framework_Resource_User
implements
    AAM_Framework_Resource_Interface,
    ArrayAccess,
    AAM_Framework_Resource_AggregateInterface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::USER;

    /**
     * Initialize the resource
     *
     * @param mixed $user_identifier
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function pre_init_hook($user_identifier)
    {
        if (!empty($user_identifier)) {
            if (is_a($user_identifier, WP_User::class)) {
                $this->_internal_id   = $user_identifier->ID;
                $this->_core_instance = AAM_Framework_Manager::_()->users->get_user(
                    $user_identifier
                );
            } elseif (is_a($user_identifier, AAM_Framework_Proxy_User::class)) {
                $this->_core_instance = $user_identifier;
                $this->_internal_id   = $user_identifier->ID;
            } elseif (!empty($user_identifier)) {
                $user = AAM_Framework_Manager::_()->users->get_user($user_identifier);

                if (is_a($user, AAM_Framework_Proxy_User::class)) {
                    $this->_core_instance = $user;
                    $this->_internal_id   = $user->ID;
                } elseif (is_scalar($user_identifier)) {
                    $this->_internal_id = $user_identifier;
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

        if (empty($this->_internal_id)) { // Assume that we are aggregating
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
                        $manager->policy->statement_to_permission(
                            $stm, self::TYPE
                        )
                    );
                }
            }
        } elseif (is_a($this->_core_instance, AAM_Framework_Proxy_User::class)) {
            $list = array_merge(
                $service->statements('User:' . $this->_internal_id),
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