<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Policy resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Policy implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POLICY;

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

                if (!empty($post_name)) {
                    $result = get_page_by_path(
                        $post_name,
                        OBJECT,
                        AAM_Framework_Service_Policies::CPT
                    );
                }
            }

            // Do some additional validation if id & post_type are provided in the
            // array
            if (is_a($result, WP_Post::class)
                && AAM_Framework_Service_Policies::CPT !== $result->post_type
            ) {
                throw new OutOfRangeException('Invalid policy instance');
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
        return $resource_identifier->ID;
    }

}