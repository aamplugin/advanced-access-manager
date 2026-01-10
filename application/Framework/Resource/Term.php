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
    protected $type = AAM_Framework_Type_Resource::TERM;

    /**
     * Term cache index
     *
     * This is done to avoid executing large volume of individual MySQL queries to DB
     * to pull post data with get_term(x) when initializing term permissions
     *
     * @var array
     *
     * @version 7.0.11
     */
    private $_term_cache_index = [];

    /**
     * Allow to implement a custom term initialization
     *
     * @return void
     * @access private
     *
     * @version 7.0.11
     */
    private function _post_init_hook()
    {
        global $wpdb;

        if (!empty($this->_permissions)) {
            // Getting list of all defined post IDs
            $ids = array_map(function($k) {
                $parts = explode('|', $k);

                return intval($parts[0]);
            }, array_keys($this->_permissions));

            // Querying the list of all terms
            $query = 'SELECT t.*, tt.* FROM ' . $wpdb->terms . ' AS t INNER JOIN '
                . $wpdb->term_taxonomy . ' AS tt ON t.term_id = tt.term_id '
                . 'WHERE t.term_id IN (' . implode(',', $ids) . ')';

            foreach($this->db->get_results($query) as $result) {
                $term_id = $result['term_id'] . '|' . $result['taxonomy'];

                $this->_term_cache_index[$term_id] = $result;
            }
        }
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Term $resource_identifier
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($identifier)
    {
        $result = [
            $identifier->term_id,
            $identifier->taxonomy
        ];

        if (property_exists($identifier, 'post_type')) {
            $result[] = $identifier->post_type;
        }

        return implode('|', $result);
    }

    /**
     * @inheritDoc
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        $parts   = explode('|', $id);
        $term_id = "{$parts[0]}|{$parts[1]}";

        // Pull it from cache of from DB
        if (array_key_exists($term_id, $this->_term_cache_index)) {
            $term = new WP_Term($this->_term_cache_index[$term_id]);
        } else {
            $term = get_term($parts[0], $parts[1]);
        }

        if (is_a($term, WP_Term::class)) {
            if (!empty($parts[2])) {
                $term->post_type = $parts[2];
            }
        }

        return $term;
    }

}