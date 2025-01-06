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
implements
    AAM_Framework_Resource_Interface,
    ArrayAccess,
    AAM_Framework_Resource_AggregateInterface
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

        if (empty($this->_internal_id)) { // Resource acts as container
            $more   = [];
            $result = [];

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
                        $more[$id] = isset($more[$id]) ? $more[$id] : [];

                        $more[$id] = array_replace(
                            $more[$id],
                            $manager->policy->statement_to_permission(
                                $stm, self::TYPE
                            )
                        );
                    }
                }
            }

            // Merging all the permissions into one cohesive array
            $rids = array_unique(array_merge(
                array_keys($permissions),
                array_keys($more)
            ));

            foreach($rids as $rid) {
                $result[$rid] = array_replace(
                    isset($more[$rid]) ? $more[$rid] : [],
                    isset($permissions[$rid]) ? $permissions[$rid] : []
                );
            }
        } else {
            // Fetching all resources that may represent our current post
            $posts = array_merge(
                $service->statements("Post:{$this->post_type}:{$this->ID}"),
                $service->statements("Post:{$this->post_type}:{$this->post_name}")
            );

            $result = $permissions;

            foreach($posts as $stm) {
                $result = array_replace(
                    $manager->policy->statement_to_permission($stm, self::TYPE),
                    $result
                );
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}