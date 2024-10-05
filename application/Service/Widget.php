<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Widget service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Widget
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.widget.enabled';

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
                    'title'       => __('Widgets', AAM_KEY),
                    'description' => __('Control the visibility of widgets on the backend and frontend for any role, user, or visitor. This service exclusively hides unwanted widgets.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 30);
        }

        if (AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG)) {
            if (is_admin()) {
                // Hook that initialize the AAM UI part of the service
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Widget::register();
                });
            }

            $this->initialize_hooks();
        }
    }

    /**
     * Initialize Widgets hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Manager WordPress metaboxes
            add_action('in_admin_header', function () {
                if (AAM_Core_Request::get('init') === 'widget') {
                    $screen = get_current_screen();

                    if ($screen && $screen->id === 'dashboard') {
                        $this->_initialize_widgets();

                        exit; // No need to load the rest of the site
                    }
                }
            }, 999);

            // Manage the list of widgets rendered on the "Appearance -> Widgets"
            // page
            add_action('widgets_admin_page', function() {
                $this->_filter_frontend_widgets();
            }, 999);

            // Manager widget's visibility on the Dashboard ->  Home page
            add_action('in_admin_header', function() {
                $screen = get_current_screen();

                if ($screen && $screen->id === 'dashboard') {
                    $this->_filter_dashboard_widgets();
                }
            }, 1000);
        } else {
            // Widget filters
            add_filter('sidebars_widgets', function($widgets) {
                $this->_filter_frontend_widgets();

                return $widgets;
            }, 999);
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
                    && $resource_type === AAM_Framework_Type_Resource::WIDGET
                ) {
                    $resource = new AAM_Framework_Resource_Widget(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_WidgetService::bootstrap();
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
     * Collect the list of all registered widgets
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _initialize_widgets()
    {
        global $wp_registered_widgets, $wp_meta_boxes;

        $cache = [
            'frontend'  => [],
            'dashboard' => []
        ];

        // Collect all registered frontend widgets
        foreach ((array)$wp_registered_widgets as $widget) {
            // Extracting class name from the registered widget
            $slug = $this->_get_widget_slug($widget, true);

            if (!empty($slug) && !isset($cache['frontend'][$slug])) {
                $cache['frontend'][$slug] = array(
                    'title' => base64_encode(wp_strip_all_tags($widget['name'])),
                    'slug'  => $slug
                );
            }
        }

        // Now collect Admin Dashboard Widgets
        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ((array) $wp_meta_boxes['dashboard'] as $levels) {
                foreach ((array) $levels as $boxes) {
                    foreach ((array) $boxes as $data) {
                        $slug = $this->_get_widget_slug($data);

                        if (!empty($slug) && !isset($cache['dashboard'][$slug])) {
                            $cache['dashboard'][$slug] = array(
                                'slug'  => $slug,
                                'title' => base64_encode(
                                    wp_strip_all_tags($data['title'])
                                )
                            );
                        }
                    }
                }
            }
        }

        AAM_Framework_Utility_Cache::set(
            AAM_Framework_Service_Widgets::CACHE_DB_OPTION, $cache, 31536000
        );
    }

    /**
     * Filter the Dashboard -> Home widgets
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_dashboard_widgets()
    {
        global $wp_meta_boxes;

        $resource = AAM::api()->widgets()->get_resource();

        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ($wp_meta_boxes['dashboard'] as $priority => $groups) {
                foreach($groups as $widgets) {
                    foreach($widgets as $widget) {
                        $slug = $this->_get_widget_slug($widget);

                        if ($resource->is_hidden($slug)) {
                            remove_meta_box($slug, 'dashboard', $priority);
                        }
                    }
                }
            }
        }
    }

    /**
     * Filter frontend widgets
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_frontend_widgets()
    {
        global $wp_registered_widgets;

        $resource = AAM::api()->widgets()->get_resource();

        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $widget) {
                $slug = $this->_get_widget_slug($widget, true);

                if ($resource->is_hidden($slug)) {
                    unregister_widget($this->_get_widget_callback($widget));

                    // Remove it from registered widget global var!!
                    // INFORM: Why Unregister Widget does not clear global var?
                    unset($wp_registered_widgets[$id]);
                }
            }
        }
    }

    /**
     * Get widget slug
     *
     * The callback is used as unique widget identifier
     *
     * @param mixed   $widget
     * @param boolean $use_cb
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_widget_slug($widget, $use_cb = false)
    {
        $result = null;

        if (!empty($widget['id']) && !$use_cb) {
            $result = str_replace('-', '_', strtolower($widget['id']));
        } else {
            $cb = $this->_get_widget_callback($widget);

            if (!is_null($cb)) { // Exclude any junk
                // Normalizing the "widget ID"
                $result = str_replace('\\', '_', strtolower($cb));
            }
        }

        return $result;
    }

    /**
     * Get widget's callback
     *
     * @param mixed $widget
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_widget_callback($widget)
    {
        $cb = null;

        if (is_array($widget['callback'])) {
            if (is_object($widget['callback'][0])) {
                $cb = get_class($widget['callback'][0]);
            } elseif (is_string($widget['callback'][0])) {
                $cb = $widget['callback'][0];
            }
        }

        if (empty($cb)) {
            $cb = isset($widget['classname']) ? $widget['classname'] : null;
        }

        return $cb;
    }

}