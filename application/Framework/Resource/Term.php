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
     * Get array of posts associated with the term
     *
     * @param array $args [optional]
     *
     * @return Generator
     *
     * @access public
     * @version 7.0.0
     */
    public function posts(array $args = []) {
        if (empty($this->_internal_id['post_type'])) {
            throw new RuntimeException('Term is not initialized with post_type');
        }

        // Get list of all posts associated with the current term
        $posts = get_posts(array_merge_recursive([
            'numberposts' => -1,
            'fields'      => 'ids',
            'post_type'   => $this->_internal_id['post_type'],
            'tax_query'   => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => $this->slug
                ]
            ]
        ]), $args);

        $result = function () use ($posts) {
            foreach ($posts as $post_id) {
                yield $this->get_access_level()->get_resource(
                    AAM_Framework_Type_Resource::POST, $post_id
                );
            }
        };

        return $result();
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
            if (isset($this->_internal_id['taxonomy'])) {
                $taxonomy = $this->_internal_id['taxonomy'];
            } else {
                $taxonomy = '';
            }

            if (isset($this->_internal_id['slug'])) {
                $term = get_term_by('slug', $this->_internal_id['slug'], $taxonomy);
            } else {
                $term = get_term($this->_internal_id['id'], $taxonomy);
            }
        }

        if (is_a($term, 'WP_Term')) {
            $this->_core_instance = $term;
        } else {
            throw new OutOfRangeException(
                "Term {$this->get_internal_id(true)} does not exist"
            );
        }
    }

}