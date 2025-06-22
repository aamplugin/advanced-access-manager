<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework utilities
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Utility_Content implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Get collection of posts
     *
     * @param array $args [Optional]
     *
     * @return Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function get_posts($query_args = [])
    {
        // Get list of all posts
        $posts = get_posts(array_merge(
            [
                'numberposts'    => 500,
                'search_columns' => [ 'post_title' ]
            ],
            $query_args,
            // Making sure that this attribute is not overwritten
            [ 'fields' => 'ids', 'suppress_filters' => true ]
        ));

        $result = function () use ($posts) {
            foreach ($posts as $post_id) {
                yield get_post($post_id);
            }
        };

        return $result();
    }

    /**
     * Get collection of post types
     *
     * If $access_level is provided, this method returns a Generator that yields a
     * collection of AAM_Framework_Resource_PostType items. Otherwise, it yields an
     * array of WP_Post_Type instances
     *
     * @param bool $return_all [Optional]
     *
     * @return Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function get_post_types($return_all = null)
    {
        // Determine the filters
        if ($return_all === true) {
            $all = true;
        } else {
            $all = AAM_Framework_Manager::_()->config->get(
                'service.post_types.manage_all', false
            );
        }

        if ($all) {
            $args = [];
        } else {
            $args = [
                'public'            => true,
                'show_ui'           => true,
                'show_in_menu'      => true,
                'show_in_rest'      => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true
            ];
        }

        // Get the list of all registered post types based on the provided
        // filters
        $post_types = get_post_types($args, 'names', 'or');

        $result = function () use ($post_types) {
            foreach ($post_types as $post_type) {
                yield get_post_type_object($post_type);
            }
        };

        return $result();
    }

    /**
     * Get collection of taxonomies
     *
     * @param bool $return_all [Optional]
     *
     * @return Generator
     * @access public
     *
     * @version 7.0.0
     */
    public function get_taxonomies($return_all = null)
    {
        // Determine the filters
        if ($return_all === true) {
            $all = true;
        } else {
            $all = AAM_Framework_Manager::_()->config->get(
                'service.taxonomies.manage_all', false
            );
        }

        if ($all) {
            $args = [];
        } else {
            $args = [
                'public'             => true,
                'show_ui'            => true,
                'show_in_rest'       => true,
                'show_in_menu'       => true,
                'show_in_quick_edit' => true,
                'show_in_nav_menus'  => true,
                'show_in_admin_bar'  => true
            ];
        }

        // Get the list of all registered taxonomies based on the provided
        // filters
        $taxonomies = get_taxonomies($args, 'names', 'or');

        $result = function () use ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                yield get_taxonomy($taxonomy);
            }
        };

        return $result();
    }

    /**
     * Get collection of terms
     *
     * If $access_level is provided, this method returns a Generator that yields a
     * collection of AAM_Framework_Resource_Term items. Otherwise, it yields an array
     * of WP_Ter instances.
     *
     * If $post_type_scope is defined, the array term resources will be scoped for
     * this give post type.
     *
     * @param array  $args            [Optional]
     * @param string $post_type_scope [Optional]
     *
     * @return Generator
     * @access public
     *
     * @version 7.0.6
     */
    public function get_terms($query_args = [], $post_type_scope = null)
    {
        // Get list of terms
        $terms = get_terms(array_merge(
            [ 'number' => 500, 'hide_empty' => false ],
            $query_args,
            // Making sure that this attribute is not overwritten
            [ 'fields' => 'ids', 'suppress_filters' => true ]
        ));

        if (!is_wp_error($terms)) {
            $cb = function () use ($terms, $post_type_scope) {
                foreach ($terms as $term_id) {
                    $term = get_term($term_id);

                    if (!empty($post_type_scope)) {
                        $term->post_type = $post_type_scope;
                    }

                    yield $term;
                }
            };

            $result = $cb();
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * Get post count
     *
     * Perform separate computation for the list of posts based on type and search
     * criteria
     *
     * @param string $post_type
     * @param string $search    [Optional]
     *
     * @return int
     *
     * @access public
     * @global WPDB $wpdb
     *
     * @version 7.0.0
     */
    public function get_post_count($post_type, $search = null)
    {
        global $wpdb;

        $query  = "SELECT COUNT(*) AS total FROM {$wpdb->posts} ";
        $query .= 'WHERE (post_type = %s)';

        if (!empty($search)) {
            $query .= ' AND (post_title LIKE %s)';
            $args   = array($post_type, "%{$search}%");
        } else {
            $args = array($post_type);
        }

        if ($post_type === 'attachment') {
            $query .= " AND ({$wpdb->posts}.post_status = %s)";
            $args[] = 'inherit';
        } else {
            $statuses = get_post_stati(array('show_in_admin_all_list' => false));

            foreach ($statuses as $status) {
                $query .= " AND ({$wpdb->posts}.post_status <> %s)";
                $args[] = $status;
            }
        }

        return $wpdb->get_var($wpdb->prepare($query, $args));
    }

    /**
     * Get terms count
     *
     * @param string $taxonomy
     * @param array  $query_args [Optional]
     *
     * @return int
     *
     * @access public
     * @global WPDB $wpdb
     *
     * @version 7.0.0
     */
    public function get_term_count($taxonomy, $query_args = null)
    {
        if (empty($query_args)) {
            $result = get_terms([
                'fields'           => 'count',
                'hide_empty'       => false,
                'suppress_filters' => true,
                'taxonomy'         => $taxonomy
            ]);
        } else {
            $result = get_terms(array_merge($query_args, [
                'fields'           => 'count',
                'suppress_filters' => true,
                'taxonomy'         => $taxonomy
            ]));
        }

        return !is_wp_error($result) ? intval($result) : 0;
    }

}