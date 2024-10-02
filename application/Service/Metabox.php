<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Metaboxes service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Metabox
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.metabox.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        if (is_admin()) {
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Metaboxes', AAM_KEY),
                    'description' => __('Control the visibility of classic backend metaboxes for any role, user, or visitor. This service exclusively hides unwanted metaboxes from the admin screens.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 30);
        }

        if (AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG)) {
            if (is_admin()) {
                // Hook that initialize the AAM UI part of the service
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Metabox::register();
                });
            }

            $this->initialize_hooks();
        }
    }

    /**
     * Initialize Metaboxes & Widgets hooks
     *
     * @return void
     *
     * @since 6.9.16 https://github.com/aamplugin/advanced-access-manager/issues/315
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.16
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Manager WordPress metaboxes
            add_action('in_admin_header', function () {
                global $post;

                if (AAM_Core_Request::get('init') === 'metabox') {
                    //make sure that nobody is playing with screen options
                    if (is_a($post, 'WP_Post')) {
                        $id = $post->post_type;
                    } else {
                        $screen = get_current_screen();
                        $id = ($screen ? $screen->id : '');
                    }

                    $this->_initialize_metaboxes($id);

                    exit; // No need to load the rest of the site
                }
            }, 999);

            // Manage Navigation Menu page to support
            add_filter('nav_menu_meta_box_object', function ($obj) {
                if (is_object($obj)) {
                    $obj->_default_query['suppress_filters'] = false;
                }

                return $obj;
            });

            // Manager WordPress metaboxes - Classic Editor
            add_action('in_admin_header', function(){
                $this->_filter_metaboxes();
            }, PHP_INT_MAX);
        }

        // Policy generation hook
        // add_filter(
        //     'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        // );

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::METABOX
                ) {
                    $resource = new AAM_Framework_Resource_Metabox(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_MetaboxService::bootstrap();
    }

    /**
     * Generate Metabox & Widget policy statements
     *
     * @param array                     $policy
     * @param string                    $resource_type
     * @param array                     $options
     * @param AAM_Core_Policy_Generator $generator
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    // public function generatePolicy($policy, $resource_type, $options, $generator)
    // {
    //     if ($resource_type === AAM_Core_Object_Metabox::OBJECT_TYPE) {
    //         if (!empty($options)) {
    //             $metaboxes = $widgets = array();

    //             foreach($options as $id => $effect) {
    //                 $parts = explode('|', $id);

    //                 if (in_array($parts[0], array('dashboard', 'widgets'), true)) {
    //                     $widgets[$id] = !empty($effect);
    //                 } else {
    //                     $metaboxes[$id] = !empty($effect);
    //                 }
    //             }

    //             $policy['Statement'] = array_merge(
    //                 $policy['Statement'],
    //                 $generator->generateBasicStatements($widgets, 'Widget'),
    //                 $generator->generateBasicStatements($metaboxes, 'Metabox')
    //             );
    //         }
    //     }

    //     return $policy;
    // }

    /**
     * Initialize list of metaboxes for given screen
     *
     * @param string $screen_id
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _initialize_metaboxes($screen_id)
    {
        global $wp_meta_boxes;

        $cache = AAM_Framework_Utility_Cache::get(
            AAM_Framework_Service_Metaboxes::CACHE_DB_OPTION, []
        );

        if (!isset($cache[$screen_id])) {
            $cache[$screen_id] = [];
        }

        if (isset($wp_meta_boxes[$screen_id])) {
            foreach ((array) $wp_meta_boxes[$screen_id] as $levels) {
                foreach ((array) $levels as $boxes) {
                    foreach ((array) $boxes as $data) {
                        if (trim($data['id'])) { //exclude any junk
                            $cache[$screen_id][$data['id']] = array(
                                'slug'  => $data['id'],
                                'title' => wp_strip_all_tags($data['title'])
                            );
                        }
                    }
                }
            }

            // Removing duplicates
            $cache[$screen_id] = array_values($cache[$screen_id]);
        }

        AAM_Framework_Utility_Cache::set(
            AAM_Framework_Service_Metaboxes::CACHE_DB_OPTION, $cache, 31536000
        );
    }

    /**
     * Handle metabox initialization process
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_metaboxes()
    {
        global $post, $wp_meta_boxes;

        // Make sure that nobody is playing with screen options
        if (is_a($post, 'WP_Post')) {
            $current_id = $post->post_type;
        } else {
            $screen     = get_current_screen();
            $current_id = ($screen ? $screen->id : null);
        }

        if (filter_input(INPUT_GET, 'init') !== 'metabox'
            && is_array($wp_meta_boxes)
        ) {
            foreach ($wp_meta_boxes as $screen_id => $zones) {
                if ($current_id === $screen_id) {
                    $this->_filter_zones($zones, $screen_id);
                }
            }
        }
    }

    /**
     * Filter metaboxes based on screen
     *
     * @param array  $zones
     * @param string $screen_id
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_zones($zones, $screen_id)
    {
        foreach ($zones as $zone => $priorities) {
            foreach ($priorities as $metaboxes) {
                $resource = AAM::api()->metaboxes()->get_resource();

                foreach (array_keys($metaboxes) as $id) {
                    if ($resource->is_hidden($screen_id, $id)) {
                        remove_meta_box($id, $screen_id, $zone);
                    }
                }
            }
        }
    }

}