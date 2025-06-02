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

    use AAM_Service_BaseTrait;

    /**
     * Collection of captured widgets
     *
     * @version 7.0.0
     */
    const CACHE_OPTION = 'aam_widgets';

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        // Register RESTful API endpoints
        AAM_Restful_Widgets::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Prepare the list of widgets
     *
     * @param AAM_Framework_Service_Widgets $service
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    public function get_widget_list($service)
    {
        $result = [];

        // Getting the menu cache so we can build the list
        $cache = AAM::api()->cache->get(self::CACHE_OPTION, []);

        if (!empty($cache) && is_array($cache)) {
            foreach($cache as $s_id => $widgets) {
                foreach($widgets as $widget) {
                    array_push($result, $this->_prepare_widget(
                        $widget, $s_id, $service
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * Initialize Widgets hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_Widget::register();
            });

            // Manager WordPress metaboxes
            add_action('in_admin_header', function () {
                $screen = get_current_screen();

                if (filter_input(INPUT_GET, 'init') === 'widget') {
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
     * Normalize and prepare the widget model
     *
     * @param array                         $widget
     * @param string                        $area
     * @param AAM_Framework_Service_Widgets $service
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_widget($widget, $area, $service)
    {
        return [
            'slug'          => $widget['slug'],
            'area'          => $area,
            'title'         => base64_decode($widget['title']),
            'is_restricted' => $service->is_denied($widget),
        ];
    }

    /**
     * Collect the list of all registered widgets
     *
     * @return void
     * @access private
     *
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
                    'area'  => 'frontend',
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
                                'area'  => 'dashboard',
                                'title' => base64_encode(
                                    wp_strip_all_tags($data['title'])
                                )
                            ];
                        }
                    }
                }
            }
        }

        AAM::api()->cache->set(self::CACHE_OPTION, $cache, 31536000);
    }

    /**
     * Filter the Dashboard -> Home widgets
     *
     * @return void
     * @access private
     *
     * @version 7.0.2
     */
    private function _filter_dashboard_widgets()
    {
        global $wp_meta_boxes;

        $service = AAM::api()->widgets();

        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ($wp_meta_boxes['dashboard'] as $priority => $groups) {
                foreach($groups as $widgets) {
                    foreach($widgets as $widget) {
                        if (!empty($widget) && $service->is_denied($widget)) {
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _filter_frontend_widgets()
    {
        global $wp_registered_widgets;

        $service = AAM::api()->widgets();

        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $widget) {
                if ($service->is_denied($widget)) {
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
     * @access private
     *
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