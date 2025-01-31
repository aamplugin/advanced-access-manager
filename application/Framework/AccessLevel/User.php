<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User access level
 *
 * @property array $caps
 * @property array $allcaps
 * @property array $roles
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_AccessLevel_User implements AAM_Framework_AccessLevel_Interface
{

    use AAM_Framework_AccessLevel_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_AccessLevel::USER;

    /**
     * Get parent access level
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_parent()
    {
        $roles        = $this->roles;
        $primary_role = array_shift($roles);

        // It is possible that user either does not have any roles or the assigned
        // role no longer exists.
        // Note! AAM does not allow deleting roles if there is at least one user
        // assigned. However, it can be delete through other plugin or custom code
        if (wp_roles()->is_role($primary_role)) {
            $parent = AAM_Framework_Manager::_()->access_levels->get(
                AAM_Framework_Type_AccessLevel::ROLE, $primary_role
            );

            // If multi-role support is enabled & there are multiple roles assigned
            // to user, then fetch them all
            $mu_role_support = AAM_Framework_Manager::_()->config->get(
                'core.settings.multi_access_levels'
            );

            if ($mu_role_support && count($roles)) {
                foreach ($roles as $role) {
                    $parent->add_sibling(AAM_Framework_Manager::_()->access_levels->get(
                        AAM_Framework_Type_AccessLevel::ROLE, $role
                    ));
                }
            }
        } else { // In this case - the Default access level is parent
            $parent = AAM_Framework_Manager::_()->access_levels->get(
                AAM_Framework_Type_AccessLevel::DEFAULT
            );
        }

        return $parent;
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return $this->_proxy_instance->ID;
    }

    /**
     * @inheritDoc
     */
    public function get_display_name()
    {
        return $this->_proxy_instance->display_name;
    }

    /**
     * Initialize the access level
     *
     * @param WP_User $core_instance
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize($core_instance)
    {
        $this->_proxy_instance = new AAM_Framework_Proxy_User(
            $core_instance
        );
    }

}