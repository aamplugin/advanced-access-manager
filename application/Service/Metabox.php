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

        if (AAM::api()->configs()->get_config(self::FEATURE_FLAG)) {
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
                    // Make sure that nobody is playing with screen options
                    if (is_a($post, 'WP_Post')) {
                        $this->_initialize_metaboxes($post->post_type);

                        exit; // No need to load the rest of the site
                    }
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

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::METABOXES
                ) {
                    $resource = new AAM_Framework_Resource_Metaboxes(
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
     * Initialize list of metaboxes for given screen
     *
     * @param string $post_type
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _initialize_metaboxes($post_type)
    {
        global $wp_meta_boxes;

        $cache = AAM_Framework_Utility_Cache::get(
            AAM_Framework_Service_Metaboxes::CACHE_DB_OPTION, []
        );

        $cache[$post_type] = []; // Reset the list

        if (isset($wp_meta_boxes[$post_type])) {
            foreach ((array) $wp_meta_boxes[$post_type] as $levels) {
                foreach ((array) $levels as $boxes) {
                    foreach ((array) $boxes as $data) {
                        if (trim($data['id'])) { //exclude any junk
                            $cache[$post_type][$data['id']] = array(
                                'slug'  => strtolower($post_type . '_' . $data['id']),
                                'title' => base64_encode(
                                    $this->_prepare_metabox_name($data)
                                )
                            );
                        }
                    }
                }
            }

            // Removing duplicates
            $cache[$post_type] = array_values($cache[$post_type]);
        }

        AAM_Framework_Utility_Cache::set(
            AAM_Framework_Service_Metaboxes::CACHE_DB_OPTION, $cache, 31536000
        );
    }

    /**
     * Normalize the component title
     *
     * @param object $item
     *
     * @return string
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_metabox_name($item)
    {
        $title = wp_strip_all_tags(
            !empty($item['title']) ? $item['title'] : $item['slug']
        );

        return ucwords(trim(preg_replace('/[\d]/', '', $title)));
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
            $post_type = $post->post_type;
        } else {
            $screen     = get_current_screen();
            $post_type = ($screen ? $screen->id : null);
        }

        if (filter_input(INPUT_GET, 'init') !== 'metabox'
            && !empty($wp_meta_boxes[$post_type])
        ) {
            $this->_filter_zones($wp_meta_boxes[$post_type], $post_type);
        }
    }

    /**
     * Filter metaboxes based on screen
     *
     * @param array  $zones
     * @param string $post_type
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_zones($zones, $post_type)
    {
        foreach ($zones as $zone => $priorities) {
            foreach ($priorities as $metaboxes) {
                $resource = AAM::api()->metaboxes()->get_resource();

                foreach (array_keys($metaboxes) as $id) {
                    if ($resource->is_hidden($post_type . '_' . $id)) {
                        remove_meta_box($id, $post_type, $zone);
                    }
                }
            }
        }
    }

}