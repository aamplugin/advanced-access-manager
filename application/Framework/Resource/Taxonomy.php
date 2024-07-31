<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Taxonomy Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Taxonomy
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TAXONOMY;

    /**
     * Return the list of all properly scoped permissions
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions()
    {
        $result = [];

        foreach($this->get_settings() as $scope => $permissions) {
            array_push(
                $result,
                ...array_map(function($permission) use ($scope) {
                    return array_merge($permission, [ 'scope' => $scope ]);
                }, $permissions)
            );
        }

        return $result;
    }

    /**
     * Initialize the core instance
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $taxonomy = get_taxonomy($this->_internal_id);

        if (is_a($taxonomy, 'WP_Taxonomy')) {
            $this->_core_instance = $taxonomy;
        } else {
            throw new OutOfRangeException(
                "Taxonomy {$this->_internal_id} does not exist"
            );
        }
    }

}