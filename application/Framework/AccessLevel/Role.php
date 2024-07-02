<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role subject
 *
 * @package AAM
 *
 * @version 6.9.34
 */
class AAM_Framework_AccessLevel_Role extends AAM_Framework_AccessLevel_Abstract
{

    /**
     * Collection of siblings
     *
     * Sibling is access level that is on the same level. For example, access level
     * role can have siblings when user is assigned to multiple roles.
     *
     * @var array
     *
     * @version 6.9.34
     */
    private $_siblings = [];

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        $levels = AAM_Framework_Manager::access_levels();

        return apply_filters(
            'aam_get_parent_access_level_filter',
            $levels->get(AAM_Framework_Type_AccessLevel::ALL),
            $this
        );
    }

    /**
     * Add new siblings to the collection
     *
     * @param AAM_Framework_AccessLevel_Role $role
     *
     * @return void
     *
     * @access public
     * @version 6.9.34
     */
    public function add_sibling(AAM_Framework_AccessLevel_Role $role)
    {
        array_push($this->_siblings, $role);
    }

    /**
     * Check if there are any siblings
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.34
     */
    public function has_siblings()
    {
        return count($this->_siblings) > 0;
    }

}