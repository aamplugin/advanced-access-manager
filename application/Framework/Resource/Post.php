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
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Post::class)) {
            $result = $resource_identifier;
        } elseif (is_numeric($resource_identifier)) {
            $result = get_post($resource_identifier);
        } elseif (is_array($resource_identifier)) {
            if (isset($resource_identifier['id'])) {
                $result = get_post($resource_identifier['id']);
            } else {
                // Let's get post_name
                if (isset($resource_identifier['slug'])) {
                    $post_name = $resource_identifier['slug'];
                } elseif (isset($resource_identifier['post_name'])) {
                    $post_name = $resource_identifier['post_name'];
                }

                if (!empty($post_name) && isset($resource_identifier['post_type'])) {
                    $result = get_page_by_path(
                        $post_name,
                        OBJECT,
                        $resource_identifier['post_type']
                    );
                }
            }

            // Do some additional validation if id & post_type are provided in the
            // array
            if (is_a($result, WP_Post::class)
                && isset($resource_identifier['post_type'])
                && $resource_identifier['post_type'] !== $result->post_type
            ) {
                throw new OutOfRangeException(
                    'The post_type does not match actual post type'
                );
            }
        }

        if (!is_a($result, WP_Post::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Post $resource_identifier
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        return [
            'id'        => $resource_identifier->ID,
            'post_type' => $resource_identifier->post_type
        ];
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result  = [];
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        foreach($service->statements('Post:*') as $stm) {
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
                        $manager->policy->statement_to_permission(
                            $stm, self::TYPE
                        )
                    );
                }
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}