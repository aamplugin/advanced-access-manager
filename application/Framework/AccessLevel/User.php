<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User subject
 *
 * @package AAM
 *
 * @version 6.9.34
 */
class AAM_Framework_AccessLevel_User extends AAM_Framework_AccessLevel_Abstract
{

    /**
     * Get parent access level
     *
     * @return AAM_Framework_AccessLevel_Abstract
     *
     * @access public
     * @version 6.9.34
     */
    public function get_parent()
    {
        $roles        = $this->roles;
        $primary_role = array_shift($roles);

        // It is possible that user either does not have any roles or the assigned
        // role no longer exists.
        // Note! AAM does not allow deleting roles if there is at least one user
        // assigned. However, it can be delete through other plugin or custom code
        if (AAM_Framework_Manager::roles()->get_wp_roles()->is_role($primary_role)) {
            $parent = AAM_Framework_Manager::access_levels()->get(
                AAM_Framework_Type_AccessLevel::ROLE, $primary_role
            );

            // If multi-role support is enabled & there are multiple roles assigned
            // to user, then fetch them all
            $mu_role_support = AAM::api()->configs()->get_config(
                'core.settings.multiSubject'
            );

            if ($mu_role_support && count($roles)) {
                foreach ($roles as $role) {
                    $parent->add_sibling(AAM_Framework_Manager::access_levels()->get(
                        AAM_Framework_Type_AccessLevel::ROLE, $role
                    ));
                }
            }
        } else { // In this case - the Default access level is parent
            $parent = AAM_Framework_Manager::access_levels()->get(
                AAM_Framework_Type_AccessLevel::DEFAULT
            );
        }

        return $parent;
    }

}