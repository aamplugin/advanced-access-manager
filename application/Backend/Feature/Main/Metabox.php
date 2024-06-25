<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend metaboxes & widgets manager
 *
 * @since 6.9.33 https://github.com/aamplugin/advanced-access-manager/issues/392
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/358
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/301
 * @since 6.7.9  https://github.com/aamplugin/advanced-access-manager/issues/192
 * @since 6.7.4  https://github.com/aamplugin/advanced-access-manager/issues/167
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.33
 */
class AAM_Backend_Feature_Main_Metabox
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * DB cache option
     *
     * @version 6.0.0
     */
    const DB_CACHE_OPTION = 'aam_metabox_cache';

    /**
     * Default access capability to the service
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_metaboxes';

    /**
     * Type of AAM core object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = AAM_Core_Object_Metabox::OBJECT_TYPE;

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'service/metabox.php';

    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     * @version 6.9.13
     */
    public function __construct()
    {
        // Customize the user experience
        add_filter('aam_component_screen_mode_panel_filter', function() {
            return AAM_Backend_View::getInstance()->loadPartial('component-screen-mode');
        });
    }

    /**
     * Prepare the Metabox & Widgets initialization process
     *
     * This method is invoked when user clicks "Refresh" button on the AAM UI
     *
     * @return string
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/358
     * @since 6.0.3  Fixed the bug where post types that do not have Gutenberg enabled
     *               are not shown on the Metaboxes & Widgets tab
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @global array $wp_post_types
     * @version 6.9.26
     */
    public function prepareInitialization()
    {
        global $wp_post_types;

        AAM_Core_Cache::set(self::DB_CACHE_OPTION, array(), 31536000);

        $endpoints = array(add_query_arg(
            'init', 'metabox', admin_url('index.php')
        ));

        foreach (array_keys($wp_post_types) as $type) {
            if ($wp_post_types[$type]->show_ui) {
                $endpoints[] = add_query_arg(
                    'init', 'metabox', admin_url('post-new.php?post_type=' . $type)
                );
            }
        }

        return wp_json_encode(
            array('status'    => 'success', 'endpoints' => $endpoints)
        );
    }

    /**
     * Initialize metabox list
     *
     * @param string $post_type
     *
     * @return void
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.17
     */
    public function initialize($post_type)
    {
        $cache = $this->getMetaboxList();

        if ($post_type === 'dashboard') {
            $this->collectWidgets($cache);
        } else {
            $this->collectMetaboxes($post_type, $cache);
        }

        AAM_Core_Cache::set(self::DB_CACHE_OPTION, $cache, 31536000); // 1 year
    }

    /**
     * Collect dashboard widgets
     *
     * @global type $wp_registered_widgets
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function collectWidgets(&$cache)
    {
        global $wp_registered_widgets;

        if (!isset($cache['widgets'])) {
            $cache['widgets'] = array();
        }

        // Get frontend widgets
        foreach ((array)$wp_registered_widgets as $data) {
            if (is_object($data['callback'][0])) {
                $callback = get_class($data['callback'][0]);
            } elseif (is_string($data['callback'][0])) {
                $callback = $data['callback'][0];
            } else {
                $callback = isset($data['classname']) ? $data['classname'] : null;
            }

            if (!is_null($callback)) { //exclude any junk
                $cache['widgets'][$callback] = array(
                    'title' => wp_strip_all_tags($data['name']),
                    'slug'  => $callback
                );
            }
        }

        // Removing duplicates
        $cache['widgets'] = array_values($cache['widgets']);

        // Now collect Admin Dashboard Widgets
        $this->collectMetaboxes('dashboard', $cache);
    }

    /**
     * Collect metaboxes
     *
     * @param type $post_type
     * @param type $cache
     *
     * @return void
     *
     * @access protected
     * @global array $wp_meta_boxes
     * @version 6.0.0
     */
    protected function collectMetaboxes($post_type, &$cache)
    {
        global $wp_meta_boxes;

        if (!isset($cache[$post_type])) {
            $cache[$post_type] = array();
        }

        if (isset($wp_meta_boxes[$post_type])) {
            foreach ((array) $wp_meta_boxes[$post_type] as $levels) {
                foreach ((array) $levels as $boxes) {
                    foreach ((array) $boxes as $data) {
                        if (trim($data['id'])) { //exclude any junk
                            $cache[$post_type][$data['id']] = array(
                                'slug'  => $data['id'],
                                'title' => wp_strip_all_tags($data['title'])
                            );
                        }
                    }
                }
            }

            // Removing duplicates
            $cache[$post_type] = array_values($cache[$post_type]);
        }
    }

    /**
     * Get list of metaboxes & widgets
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.17
     */
    public function getMetaboxList()
    {
        global $wp_post_types;

        $cache = AAM_Core_Cache::get(self::DB_CACHE_OPTION);

        if (!is_array($cache)) {
            $cache = array();
        }

        // If visitor, return only frontend widgets
        if (AAM_Backend_Subject::getInstance()->isVisitor()) {
            if (!empty($cache['widgets'])) {
                $response = array('widgets' => $cache['widgets']);
            } else {
                $response = array();
            }
        } else {
            $response = $cache;
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
     * Register metabox service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'metabox',
            'position'   => 10,
            'title'      => __('Metaboxes & Widgets', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID,
                AAM_Core_Subject_User::UID,
                AAM_Core_Subject_Visitor::UID,
                AAM_Core_Subject_Default::UID
            ),
            'view'        => __CLASS__
        ));
    }

}