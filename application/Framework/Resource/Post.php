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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * Determine if post is hidden on given area
     *
     * @param string $area Can be either frontend, backend or api
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden_on($area)
    {
        return !empty($this->_permissions['list'])
            && in_array($area, $this->_permissions['list']['on'], true)
            && $this->_permissions['list']['effect'] == 'deny';
    }

    /**
     * Determine if post is hidden on currently viewed area
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden()
    {
        if (is_admin()) {
            $area = 'backend';
        } elseif (defined('REST_REQUEST') && REST_REQUEST) {
            $area = 'api';
        } else {
            $area = 'frontend';
        }

        return $this->is_hidden_on($area);
    }

    /**
     * @inheritDoc
     */
    public function merge_permissions($permissions)
    {
        $result           = [];
        $base_permissions = $this->get_permissions();

        $permission_list = array_unique(
            [...array_keys($base_permissions), ...array_keys($permissions)]
        );

        $config = AAM::api()->configs();

        // Determine permissions merging preference
        $merging_preference = strtolower($config->get_config(
            'core.settings.' . self::TYPE . '.merge.preference',
            $config->get_config('core.settings.merge.preference')
        ));
        $default_effect = $merging_preference === 'allow' ? 'allow' : 'deny';

        foreach($permission_list as $perm) {
            $effect_a = null;
            $effect_b = null;

            if (isset($base_permissions[$perm])) {
                $effect_a = $base_permissions[$perm]['effect'];
            }

            if (isset($permissions[$perm])) {
                $effect_b = $permissions[$perm]['effect'];
            }

            if ($default_effect === 'allow') { // Merging preference is to allow
                if (in_array($effect_a, [ 'allow', null ], true)
                    || in_array($effect_b, [ 'allow', null ], true)
                ) {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                } elseif (!is_null($effect_b)) {
                    $result[$perm] = $permissions[$perm];
                } else {
                    $result[$perm] = $base_permissions[$perm];
                }
            } else { // Merging preference is to deny access by default
                if ($effect_b === 'deny') {
                    $result[$perm] = $permissions[$perm];
                } elseif ($effect_a === 'deny') {
                    $result[$perm] = $base_permissions[$perm];
                } else {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                }
            }
        }

        return $result;
    }

    /**
     * Initialize additional properties
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $post = get_post($this->_internal_id);

        if (is_a($post, 'WP_Post')) {
            $this->_core_instance = $post;
        } else {
            throw new OutOfRangeException(
                "Post with ID {$this->_internal_id} does not exist"
            );
        }
    }

}