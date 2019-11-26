<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Backend metaboxes & widgets manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Main_Metabox
    extends AAM_Backend_Feature_Abstract implements AAM_Backend_Feature_ISubjectAware
{

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
     * Save metabox access settings
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $items  = AAM_Core_Request::post('items', array());
        $status = AAM_Core_Request::post('status');

        $object = AAM_Backend_Subject::getInstance()->getObject(
            self::OBJECT_TYPE, null, true
        );

        foreach ($items as $item) {
            $object->updateOptionItem($item, $status);
        }

        return wp_json_encode(
            array('status' => ($object->save() ? 'success' : 'failure'))
        );
    }

    /**
     * Prepare the Metabox & Widgets initialization process
     *
     * This method is invoked when user clicks "Refresh" button on the AAM UI
     *
     * @return string
     *
     * @since 6.0.3 Fixed the bug where post types that do not have Gutenberg enabled
     *              are not shown on the Metaboxes & Widgets tab
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @global array $wp_post_types
     * @version 6.0.3
     */
    public function prepareInitialization()
    {
        global $wp_post_types;

        AAM_Core_API::deleteOption(self::DB_CACHE_OPTION);

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
     * @access public
     * @version 6.0.0
     */
    public function initialize($post_type)
    {
        $cache = $this->getMetaboxList();

        if ($post_type === 'dashboard') {
            $this->collectWidgets($cache);
        } else {
            $this->collectMetaboxes($post_type, $cache);
        }

        AAM_Core_API::updateOption(self::DB_CACHE_OPTION, $cache);
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
                    'id'    => $callback
                );
            }
        }

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
                                'id'    => $data['id'],
                                'title' => wp_strip_all_tags($data['title'])
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Get list of metaboxes & widgets
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getMetaboxList()
    {
        global $wp_post_types;

        $cache = AAM_Core_API::getOption(self::DB_CACHE_OPTION, array());

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