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
                    'description' => __('Control the visibility of widgets on the backend and frontend for any role, user, or visitor. This service exclusively hides unwanted widgets without preventing direct data manipulation or spoofing.', AAM_KEY),
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

                    $model = new AAM_Backend_Feature_Main_Metabox;
                    $model->initialize($id);
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
            add_action('in_admin_header', array($this, 'filterMetaboxes'), 999);

            // Manage Dashboard widgets
            add_action('widgets_admin_page', array($this, 'filterMetaboxes'), 999);
        } else {
            // Widget filters
            add_filter('sidebars_widgets', array($this, 'filterWidgets'), 999);
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
                    && $resource_type === AAM_Framework_Type_Resource::COMPONENT
                ) {
                    $resource = new AAM_Framework_Resource_Component(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_ComponentService::bootstrap();
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
     * Handle metabox initialization process
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function filterMetaboxes()
    {
        global $post;

        // Make sure that nobody is playing with screen options
        if (is_a($post, 'WP_Post')) {
            $id = $post->post_type;
        } else {
            $screen = get_current_screen();
            $id     = ($screen ? $screen->id : null);
        }

        if (filter_input(INPUT_GET, 'init') !== 'metabox') {
            if ($id !== 'widgets') {
               $this->filterBackend($id);
            } else {
                $this->filterAppearanceWidgets();
            }
        }
    }

    /**
     * Filter frontend widgets
     *
     * @param array $widgets
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function filterWidgets($widgets)
    {
        global $wp_registered_widgets;

        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::METABOX
        );

        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $widget) {
                $callback = $this->getWidgetCallback($widget);
                if ($resource->is_hidden('widgets', $callback)) {
                    unregister_widget($callback);
                    // Remove it from registered widget global var!!
                    // INFORM: Why Unregister Widget does not clear global var?
                    unset($wp_registered_widgets[$id]);
                }
            }
        }

        return $widgets;
    }

    /**
     * Get list of all cached components
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.9.13 Initial implementation of the method
     *
     * @access public
     * @version 6.9.17
     */
    public function getComponentsCache()
    {
        global $wp_post_types;

        $response = AAM_Framework_Utility_Cache::get(
            AAM_Backend_Feature_Main_Metabox::DB_CACHE_OPTION
        );

        if (!is_array($response)) {
            $response = array();
        }

        // Filter non-existing metaboxes
        foreach (array_keys($response) as $id) {
            if (
                !in_array($id, array('dashboard', 'widgets'), true)
                && empty($wp_post_types[$id])
            ) {
                unset($response[$id]);
            }
        }

        return $response;
    }

    /**
     * Filter backend metaboxes and widgets
     *
     * @param string $screen
     *
     * @since 6.4.0 Making the method protected
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @global array $wp_meta_boxes
     * @version 6.0.0
     */
    protected function filterBackend($screen)
    {
        global $wp_meta_boxes;

        if (is_array($wp_meta_boxes)) {
            foreach ($wp_meta_boxes as $screen_id => $zones) {
                if ($screen === $screen_id) {
                    $this->filterZones($zones, $screen_id);
                }
            }
        }
    }

    /**
     * Filter of widgets on the Appearance->Widgets screen
     *
     * @access protected
     *
     * @since 6.4.0 Making the method protected
     * @since 6.0.0 Initial implementation of the method
     *
     * @return void
     * @global array $wp_registered_widgets
     * @version 6.0.0
     */
    protected function filterAppearanceWidgets()
    {
        global $wp_registered_widgets;

        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::METABOX
        );

        foreach ($wp_registered_widgets as $id => $widget) {
            $callback = $this->getWidgetCallback($widget);
            if ($resource->is_hidden('widgets', $callback)) {
                unregister_widget($callback);
                unset($wp_registered_widgets[$id]);
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
     * @access protected
     * @version 6.0.0
     */
    protected function filterZones($zones, $screen_id)
    {
        foreach ($zones as $zone => $priorities) {
            foreach ($priorities as $metaboxes) {
                $this->removeMetaboxes($zone, $metaboxes, $screen_id);
            }
        }
    }

    /**
     * Filter list of metaboxes on the screen
     *
     * @param string $zone
     * @param array  $metaboxes
     * @param string $screen_id
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function removeMetaboxes($zone, $metaboxes, $screen_id)
    {
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::METABOX
        );

        foreach (array_keys($metaboxes) as $id) {
            if ($resource->is_hidden($screen_id, $id)) {
                remove_meta_box($id, $screen_id, $zone);
            }
        }
    }

    /**
     * Get widget's callback
     *
     * The callback is used as unique widget identifier
     *
     * @param mixed $widget
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getWidgetCallback($widget)
    {
        if (is_array($widget['callback'])) {
            if (is_object($widget['callback'][0])) {
                $callback = get_class($widget['callback'][0]);
            } elseif (is_string($widget['callback'][0])) {
                $callback = $widget['callback'][0];
            }
        }

        if (empty($callback)) {
            $callback = isset($widget['classname']) ? $widget['classname'] : null;
        }

        return $callback;
    }

}