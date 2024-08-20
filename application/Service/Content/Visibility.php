<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Content visibility handler
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Content_Visibility
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_instance = null;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $_settings = null;

    /**
     * Undocumented function
     */
    protected function __construct()
    {
        // Trigger the inheritance chain and prepare the complete content visibility
        // tree
        $visibility_settings = $this->_prepare_access_level_visibility(
            [ 'post' => [] ], AAM::api()->user(), AAM_Framework_Type_Resource::POST
        );

        // Enrich post controls with additional information about post type so we can
        // better prepare queries
        if (!empty($visibility_settings['post'])) {
            $visibility_settings = $this->_enrich_post_visibility_settings(
                $visibility_settings
            );
        }

        // Allow other plugins to influence content visibility initialization process
        $this->_settings = apply_filters(
            'aam_content_visibility_filter',
            $visibility_settings,
            AAM::api()->user(),
            function($visibility, $access_level, $resource_type) {
                return $this->_prepare_access_level_visibility(
                    $visibility, $access_level, $resource_type
                );
            }
        );

        //echo '<pre>'; print_r($this->_visibility_settings); echo '</pre>';
    }

    /**
     * Get collection of visibility settings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_settings()
    {
        return $this->_settings;
    }

    /**
     * Undocumented function
     *
     * @param [type] $wp_query
     * @return void
     */
    public function prepare_post_query($wp_query)
    {
        global $wpdb;

        if (!empty($wp_query->query['post_type'])) {
            $post_type = $wp_query->query['post_type'];
        } elseif (!empty($wp_query->query_vars['post_type'])) {
            $post_type = $wp_query->query_vars['post_type'];
        } elseif ($wp_query->is_attachment) {
            $post_type = 'attachment';
        } elseif ($wp_query->is_page) {
            $post_type = 'page';
        } else {
            $post_type = 'any';
        }

        if ($post_type === 'any') {
            $post_type = array_keys(get_post_types(array(), 'names'));
        }

        $post_types = (array) $post_type;
        $not_in     = [];

        foreach ($this->_settings['post'] as $id => $control) {
            if ($this->_is_hidden($control)
                // It is possible that we have defined access control to posts that
                // were already deleted, this is why we should verify that meta prop
                // was set in the _enrich_post_visibility_settings method
                && array_key_exists('__post_type', $control)
                && in_array($control['__post_type'], $post_types, true)
            ) {
                $not_in[] = $id;
            }
        }

        if (!empty($not_in)) {
            $query = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $not_in) . ")";
        } else {
            $query = '';
        }

        return $query;
    }

    /**
     * Undocumented function
     *
     * @param [type] $settings
     * @return void
     */
    private function _enrich_post_visibility_settings($settings)
    {
        global $wpdb;

        $ids     = implode(',', array_map('intval', array_keys($settings['post'])));
        $results = $wpdb->get_results(
            "SELECT ID, post_type FROM {$wpdb->prefix}posts WHERE ID IN ({$ids})"
        );

        foreach($results as $row) {
            $settings['post'][$row->ID]['__post_type'] = $row->post_type;
        }

        return $settings;
    }

    /**
     * Determine if post is hidden on currently viewed area
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_hidden($control)
    {
        if (is_admin()) {
            $area = 'backend';
        } elseif (defined('REST_REQUEST') && REST_REQUEST) {
            $area = 'api';
        } else {
            $area = 'frontend';
        }

        return in_array($area, $control['on'], true) && $control['effect'] == 'deny';
    }

    /**
     * Undocumented function
     *
     * @param array                               $visibility
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param string                              $resource_type
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_access_level_visibility(
        $visibility, $access_level, $resource_type
    ) {
        // Get the complete list of access controls for give resource type
        $controls = AAM::api()->settings([
            'access_level' => $access_level
        ])->get_setting($resource_type, []);

        // Determine if the access level has parent level
        $parent_access_level = $access_level->get_parent();

        // Merge access settings if multi access levels config is enabled
        $multi_support = AAM::api()->configs()->get_config(
            'core.settings.multi_access_levels'
        );

        if ($multi_support && $parent_access_level->has_siblings()) {
            foreach ($parent_access_level->get_siblings() as $sibling) {
                $sibling_controls = AAM::api()->settings()->get_setting(
                    $resource_type, [], $sibling
                );

                $controls = $this->_merge_sibling_controls(
                    $controls, $sibling_controls, $resource_type
                );
            }
        }

        foreach ($controls as $resource_id => $settings) {
            // Lower access level always overrides the higher access level settings
            if (array_key_exists('list', $settings)
                && !isset($visibility[$resource_type][$resource_id])
            ) {
                $visibility[$resource_type][$resource_id] = array_merge(
                    $settings['list'], [ '__is_overwritten' => true ]
                );
            }
        }

        if (!is_null($parent_access_level)) {
            $visibility = $this->_prepare_access_level_visibility(
                $visibility, $parent_access_level, $resource_type
            );
        }

        return $visibility;
    }

    /**
     * Undocumented function
     *
     * @param [type] $set_a
     * @param [type] $set_b
     * @param string $resource_type
     *
     * @return void
     */
    private function _merge_sibling_controls($set_a, $set_b, $resource_type)
    {
        $result     = [];
        $unique_ids = array_unique([...array_keys($set_a), ...array_keys($set_b)]);

        foreach($unique_ids as $id) {
            if (array_key_exists($id, $set_a) && array_key_exists($id, $set_b)) {
                $result[$id] = $this->_merge_content_permissions(
                    $set_a[$id], $set_b[$id], $resource_type
                );
            } elseif (array_key_exists($id, $set_a)) {
                $result[$id] = $set_a[$id];
            } else {
                $result[$id] = $set_b[$id];
            }
        }

        return $result;
    }

    /**
     * Merge content permissions according to access controls merging preference
     *
     * @param array  $set_a
     * @param array  $set_b
     * @param string $resource_type
     *
     * @return array
     *
     * @access private
     */
    private function _merge_content_permissions($set_a, $set_b, $resource_type)
    {
        $result = [];

        $permission_list = array_unique(
            [...array_keys($set_a), ...array_keys($set_b)]
        );

        $config = AAM::api()->configs();

        // Determine permissions merging preference
        $merging_preference = strtolower($config->get_config(
            'core.settings.' . $resource_type . '.merge.preference',
            $config->get_config('core.settings.merge.preference')
        ));
        $default_effect = $merging_preference === 'allow' ? 'allow' : 'deny';

        foreach($permission_list as $perm) {
            $effect_a = isset($set_a[$perm]) ? $set_a[$perm]['effect'] : null;
            $effect_b = isset($set_b[$perm]) ? $set_b[$perm]['effect'] : null;

            if ($default_effect === 'allow') { // Merging preference is to allow
                if (in_array($effect_a, [ 'allow', null ], true)
                    || in_array($effect_b, [ 'allow', null ], true)
                ) {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                } elseif (!is_null($effect_b)) {
                    $result[$perm] = $set_b[$perm];
                } else {
                    $result[$perm] = $set_a[$perm];
                }
            } else { // Merging preference is to deny access by default
                if ($effect_b === 'deny') {
                    $result[$perm] = $set_b[$perm];
                } elseif ($effect_a === 'deny') {
                    $result[$perm] = $set_a[$perm];
                } else {
                    $result[$perm] = [ 'permission' => $perm, 'effect' => 'allow' ];
                }
            }
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @return AAM_Service_Content_Visibility
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}