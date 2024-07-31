<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Term Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Term
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TERM;

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
        if (is_numeric($this->_internal_id)) {
            $term = get_term($this->_internal_id);
        } elseif (is_array($this->_internal_id)) { // Narrowed down with taxonomy?
            // Based on the WP DB structure, the wp_terms table contains the unique
            // list of all terms, however, the same term can be associated with
            // multiple taxonomies. The table wp_term_taxonomy has the UNIQUE KEY
            // `term_id_taxonomy` (`term_id`,`taxonomy`). Looking deeper, you notice
            // that the table wp_term_relationships is the one that actually associate
            // terms with other content types (e.g. posts) and this table uses
            // term_taxonomy_id for associations.
            $term = get_term(
                $this->_internal_id['id'],
                $this->_internal_id['taxonomy']
            );
        }

        if (is_a($term, 'WP_Term')) {
            $this->_core_instance = $term;
        } else {
            throw new OutOfRangeException(
                "Term {$this->get_internal_id()} does not exist"
            );
        }
    }

}