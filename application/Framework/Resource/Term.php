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
implements AAM_Framework_Resource_Interface, ArrayAccess
{

    use AAM_Framework_Resource_BaseTrait;

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
        if (!empty($this->_internal_id) && $serialize) {
            $result = implode('|', array_values($this->_internal_id));
        } else {
            $result = $this->_internal_id;
        }

        return $result;
    }

    /**
     * Initialize the core instance
     *
     * @param mixed $identifier
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function pre_init_hook($identifier)
    {
        if (!empty($identifier)) {
            $term = null;

            if (is_numeric($identifier)) {
                $term = get_term($identifier);
            } elseif (is_array($identifier) && isset($identifier['term'])) {
                if (is_a($identifier['term'], WP_Term::class)) {
                    $term = $identifier['term'];
                } else {
                    throw new InvalidArgumentException(
                        'The term property is not a valid WP_Term instance'
                    );
                }
            } elseif (is_array($identifier)) {
                // Based on the WP DB structure, the wp_terms table contains the unique
                // list of all terms, however, the same term can be associated with
                // multiple taxonomies. The table wp_term_taxonomy has the UNIQUE KEY
                // `term_id_taxonomy` (`term_id`,`taxonomy`). Looking deeper, you notice
                // that the table wp_term_relationships is the one that actually associate
                // terms with other content types (e.g. posts) and this table uses
                // term_taxonomy_id for associations.
                if (!empty($identifier['taxonomy'])) {
                    $taxonomy = $identifier['taxonomy'];
                } else {
                    $taxonomy = '';
                }

                if (isset($identifier['slug']) && !empty($taxonomy)) {
                    $term = AAM_Framework_Manager::_()->misc->get_term_by_slug(
                        $identifier['slug'], $taxonomy
                    );
                } elseif (!empty($identifier['id'])) {
                    $term = get_term($identifier['id'], $taxonomy);
                }
            } elseif(is_a($identifier, WP_Term::class)) {
                $term = $identifier;
            }

            if (is_a($term, WP_Term::class)) {
                $this->_core_instance = $term;

                // Preparing the internal ID
                $this->_internal_id = [
                    'id'       => $term->term_id,
                    'taxonomy' => $term->taxonomy
                ];

                if (is_array($identifier) && !empty($identifier['post_type'])) {
                    $this->_internal_id['post_type'] = $identifier['post_type'];
                }
            }
        }
    }

    /**
     * Normalize permission model further
     *
     * @param array  $permission
     * @param string $permission_key
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_permission($permission, $permission_key)
    {
        if ($permission_key === 'list'
            && (!array_key_exists('on', $permission) || !is_array($permission['on']))
        ) {
            $permission['on'] = [
                'frontend',
                'backend',
                'api'
            ];
        }

        return $permission;
    }

}