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

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TERM;

    /**
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
    {
        $result = null;

        if (is_numeric($resource_identifier)) {
            $result = get_term($resource_identifier);
        } elseif (is_array($resource_identifier) && isset($identifier['term'])) {
            $result = $identifier['term'];
        } elseif (is_array($resource_identifier)) {
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

            if (isset($resource_identifier['slug']) && !empty($taxonomy)) {
                $result = AAM_Framework_Manager::_()->misc->get_term_by_slug(
                    $resource_identifier['slug'], $taxonomy
                );
            } elseif (!empty($resource_identifier['id'])) {
                $result = get_term($resource_identifier['id'], $taxonomy);
            }
        } elseif(is_a($resource_identifier, WP_Term::class)) {
            $result = $resource_identifier;
        }

        if (!is_a($result, WP_Term::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        // If term resource is scoped for a specific post type, add it to the
        // term object
        if (is_array($resource_identifier)
            && !empty($resource_identifier['post_type'])
        ) {
            $result->post_type = $resource_identifier['post_type'];
        }

        return $result;
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Term $resource_identifier
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        $result = [
            'id'       => $resource_identifier->term_id,
            'taxonomy' => $resource_identifier->taxonomy
        ];

        if (property_exists($resource_identifier, 'post_type')) {
            $result['post_type'] = $resource_identifier->post_type;
        }

        return $result;
    }

}