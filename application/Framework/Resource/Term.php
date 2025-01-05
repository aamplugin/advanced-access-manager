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
 * @method bool is_hidden() **[Premium Feature!]** Available only with
 *         premium add-on
 * @method bool is_hidden_on(string $area) **[Premium Feature!]** Available only with
 *         premium add-on
 * @method bool|null is_restricted() **[Premium Feature!]** Available only with
 *         premium add-on
 * @method bool|null is_allowed_to(string $permission) **[Premium Feature!]** Available
 *         only with premium add-on
 * @method bool|null is_denied_to(string $permission) **[Premium Feature!]** Available
 *         only with premium add-on
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Term implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_ContentTrait, AAM_Framework_Resource_BaseTrait{
        AAM_Framework_Resource_ContentTrait::_get_settings_ns insteadof AAM_Framework_Resource_BaseTrait;
        AAM_Framework_Resource_ContentTrait::_normalize_permission insteadof AAM_Framework_Resource_BaseTrait;
    }

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TERM;

    /**
     * @inheritDoc
     */
    public function get_internal_id($serialize = true)
    {
        // Overriding the default serialization method to ensure that term's
        // compound ID is serialized with keys in correct order:
        // term_id|<taxonomy>|<post_type>
        if (is_array($this->_internal_id) && $serialize) {
            $parts = [];

            foreach([ 'id', 'slug', 'taxonomy', 'post_type' ] as $prop) {
                if (array_key_exists($prop, $this->_internal_id)) {
                    array_push($parts, $this->_internal_id[$prop]);
                }
            }

            $result = implode('|', $parts);
        } else {
            $result = $this->_internal_id;
        }

        return $result;
    }

    /**
     * Initialize the core instance
     *
     * @param mixed $resource_identifier
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        $term = null;

        if (is_numeric($resource_identifier)) {
            $term = get_term($resource_identifier);
        } elseif (is_array($resource_identifier)) { // Narrowed down with taxonomy?
            // Based on the WP DB structure, the wp_terms table contains the unique
            // list of all terms, however, the same term can be associated with
            // multiple taxonomies. The table wp_term_taxonomy has the UNIQUE KEY
            // `term_id_taxonomy` (`term_id`,`taxonomy`). Looking deeper, you notice
            // that the table wp_term_relationships is the one that actually associate
            // terms with other content types (e.g. posts) and this table uses
            // term_taxonomy_id for associations.
            if (!empty($resource_identifier['taxonomy'])) {
                $taxonomy = $resource_identifier['taxonomy'];
            } else {
                $taxonomy = '';
            }

            if (isset($resource_identifier['slug'])) {
                $term = AAM_Framework_Manager::_()->misc->get_term_by_slug(
                    $resource_identifier['slug'], $taxonomy
                );
            } elseif (!empty($resource_identifier['id'])) {
                $term = get_term($resource_identifier['id'], $taxonomy);
            }
        } elseif(is_a($resource_identifier, WP_Term::class)) {
            $term = $resource_identifier;
        }

        if (is_a($term, WP_Term::class)) {
            $this->_core_instance = $term;

            // Preparing the internal ID
            $this->_internal_id = [
                'id'       => $term->term_id,
                'taxonomy' => $term->taxonomy
            ];

            if (!empty($resource_identifier['post_type'])) {
                $this->_internal_id['post_type'] = $resource_identifier['post_type'];
            }
        } else {
            throw new OutOfRangeException('The term resource identifier is invalid');
        }
    }

}