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
                    $post = get_page_by_path($bits[2], OBJECT, $bits[1]);

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