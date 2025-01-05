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
implements AAM_Framework_Resource_Interface, ArrayAccess
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * Initialize additional properties
     *
     * @param mixed $resource_identifier
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        if (!empty($resource_identifier)) {
            if (is_a($resource_identifier, WP_Post::class)) {
                $post = $resource_identifier;
            } elseif (is_numeric($resource_identifier)) {
                $post = get_post($resource_identifier);
            } elseif (is_array($resource_identifier)) {
                if (isset($resource_identifier['id'])) {
                    $post = get_post($resource_identifier['id']);
                } else {
                    // Let's get post_name
                    if (isset($resource_identifier['slug'])) {
                        $post_name = $resource_identifier['slug'];
                    } elseif (isset($resource_identifier['post_name'])) {
                        $post_name = $resource_identifier['post_name'];
                    }

                    if (!empty($post_name) && isset($resource_identifier['post_type'])) {
                        $post = get_page_by_path(
                            $post_name,
                            OBJECT,
                            $resource_identifier['post_type']
                        );
                    }
                }

                // Do some additional validation if id & post_type are provided in the
                // array
                if (is_a($post, WP_Post::class)
                    && isset($resource_identifier['post_type'])
                    && $resource_identifier['post_type'] !== $post->post_type
                ) {
                    throw new OutOfRangeException(
                        'The post_type does not match actual post type'
                    );
                }
            }

            if (is_a($post, WP_Post::class)) {
                $this->_core_instance = $post;
                $this->_internal_id   = [
                    'id'        => $post->ID,
                    'post_type' => $post->post_type
                ];
            } else {
                throw new OutOfRangeException('The resource identifier is invalid');
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

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        $manager = AAM_Framework_Manager::_();
        $service = $manager->policies($this->get_access_level());

        // Fetching all resources that may represent our current post and doing some
        // additional validation as the same post can be targeted by both ID & slug
        $by_id   = $service->statements("Post:{$this->post_type}:{$this->ID}");
        $by_slug = $service->statements("Post:{$this->post_type}:{$this->post_name}");

        if (!empty($by_id) && !empty($by_slug)) {
            _doing_it_wrong(
                __CLASS__ . '::' . __METHOD__,
                'Found the same post by ID & slug. May lead to unexpected results.',
                AAM_VERSION
            );
        }

        foreach(array_merge($by_id, $by_slug) as $stm) {
            $permissions = array_replace(
                $manager->policy->statement_to_permission($stm, 'post'),
                $permissions
            );
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}