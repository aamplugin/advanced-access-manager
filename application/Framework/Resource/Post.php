<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Resource class
 *
 * @method WP_Post get_core_instance()
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post
implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::POST;

    /**
     * Post cache index
     *
     * This is done to avoid executing large volume of individual MySQL queries to DB
     * to pull post data with get_post(x) when initializing post permissions
     *
     * @var array
     *
     * @version 7.0.11
     */
    private $_post_cache_index = [];

    /**
     * Allow to implement a custom post initialization
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

            // Querying the list of all posts
            $query = 'SELECT ID, post_type, post_author FROM '
                .  $wpdb->posts . ' WHERE ID IN (' . implode(',', $ids) . ')';

            foreach($this->db->get_results($query) as $result) {
                $this->_post_cache_index[$result['ID']] = $result;
            }
        }
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Post $resource_identifier
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        return "{$resource_identifier->ID}|{$resource_identifier->post_type}";
    }

    /**
     * @inheritDoc
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        $parts = explode('|', $id);

        if (array_key_exists($parts[0], $this->_post_cache_index)) {
            $result = new WP_Post($this->_post_cache_index[$parts[0]]);
        } else {
            $result = get_post($parts[0]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @version 7.0.6
     */
    private function _apply_policy()
    {
        $result = [];

        foreach($this->policies()->statements('Post:*') as $stm) {
            $bits = explode(':', $stm['Resource']);

            if (count($bits) === 3) {
                // Preparing correct internal post ID
                if (is_numeric($bits[2])) {
                    $id = "{$bits[2]}|{$bits[1]}";
                } else {
                    $post = $this->misc->get_post_by_slug($bits[2], $bits[1]);

                    if (is_a($post, WP_Post::class)) {
                        $id = "{$post->ID}|{$post->post_type}";
                    } else {
                        $id = null;
                    }
                }

                if (!empty($id)) {
                    $result[$id] = isset($result[$id]) ? $result[$id] : [];

                    $result[$id] = array_replace(
                        $result[$id],
                        $this->policy->statement_to_permission(
                            $stm, $this->type
                        )
                    );
                }
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}