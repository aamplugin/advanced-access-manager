<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role access level
 *
 * @property array $capabilities
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_AccessLevel_Role implements AAM_Framework_AccessLevel_Interface
{

    use AAM_Framework_AccessLevel_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_AccessLevel::ROLE;

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        return apply_filters(
            'aam_get_parent_access_level_filter',
            AAM_Framework_Manager::_()->access_levels->get(
                AAM_Framework_Type_AccessLevel::ALL
            ),
            $this
        );
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return $this->_proxy_instance->slug;
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
     * @param WP_Role $core_instance
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize($core_instance)
    {
        $this->_proxy_instance = new AAM_Framework_Proxy_Role(
            wp_roles()->role_names[$core_instance->name],
            $core_instance
        );
    }

}