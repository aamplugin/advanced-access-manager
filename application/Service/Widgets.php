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
class AAM_Service_Widgets
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

        if (AAM::api()->config->get(self::FEATURE_FLAG)) {
            if (is_admin()) {
                // Hook that initialize the AAM UI part of the service
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Widget::register();
                });
            }

            // Register RESTful API endpoints
            AAM_Restful_WidgetService::bootstrap();

            $this->initialize_hooks();
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::WIDGET
                ) {
                    $resource = new AAM_Framework_Resource_Widget(
                        $access_level, $resource_id
                    );
                }

                return $resource;
            }, 10, 4
        );
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
                $screen = get_current_screen();

                if (AAM_Core_Request::get('init') === 'widget') {
                    if ($screen && $screen->id === 'dashboard') {
                        $this->_initialize_widgets();

                        exit; // No need to load the rest of the site
                    }
                } elseif ($screen && $screen->id === 'dashboard') {
                    $this->_filter_dashboard_widgets();
                }
            }, PHP_INT_MAX);

            // Manage the list of widgets rendered on the "Appearance -> Widgets"
            // page
            add_action('widgets_admin_page', function() {
                $this->_filter_frontend_widgets();
            }, PHP_INT_MAX);
        } else {
            // Widget filters
            add_filter('sidebars_widgets', function($widgets) {
                $this->_filter_frontend_widgets();

                return $widgets;
            }, PHP_INT_MAX);
        }
    }

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
            $slug = $this->_get_widget_slug($widget);

            if (!empty($slug)) {
                $cache['frontend'][$slug] = [
                    'title' => base64_encode(wp_strip_all_tags($widget['name'])),
                    'type'  => 'frontend',
                    'slug'  => $slug
                ];
            }
        }

        // Now collect Admin Dashboard Widgets
        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ((array) $wp_meta_boxes['dashboard'] as $levels) {
                foreach ((array) $levels as $boxes) {
                    foreach ((array) $boxes as $data) {
                        $slug = $this->_get_widget_slug($data);

                        if (!empty($slug)) {
                            $cache['dashboard'][$slug] = [
                                'slug'  => $slug,
                                'type'  => 'backend',
                                'title' => base64_encode(
                                    wp_strip_all_tags($data['title'])
                                )
                            ];
                        }
                    }
                }
            }
        }

        AAM::api()->cache->set(
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

        $service = AAM::api()->widgets();

        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ($wp_meta_boxes['dashboard'] as $priority => $groups) {
                foreach($groups as $widgets) {
                    foreach($widgets as $widget) {
                        if ($service->is_restricted($widget)) {
                            remove_meta_box($widget['id'], 'dashboard', $priority);
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

        $service = AAM::api()->widgets();

        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $widget) {
                if ($service->is_restricted($widget)) {
                    // We do not know if widget was registered with widget instance
                    // or a class name. This is why we are trying to remove both
                    // ways
                    if (is_array($widget['callback'])
                        && is_object($widget['callback'][0])
                    ) {
                        unregister_widget(spl_object_hash($widget['callback'][0]));
                        unregister_widget(get_class($widget['callback'][0]));
                    }

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
     * @param array $widget
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_widget_slug($widget)
    {
        $slug = null;

        if (!empty($widget['callback'])) {
            $slug = AAM::api()->misc->callable_to_slug($widget['callback']);
        }

        return $slug;
    }

}