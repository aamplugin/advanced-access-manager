<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Toolbar service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_AdminToolbar
{

    use AAM_Service_BaseTrait;

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
        AAM_Restful_AdminToolbar::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize Admin Toolbar hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_AdminToolbar::register();
            });
        }

        // Cache admin toolbar
        if ((is_admin() && filter_input(INPUT_GET, 'page') === 'aam')) {
            add_action('wp_after_admin_bar_render', function() {
                AAM::api()->admin_toolbar()->get_items();
            });
        }

        add_action('wp_before_admin_bar_render', function () {
            $this->_filter_admin_toolbar();
        }, PHP_INT_MAX, 0, true);
    }

    /**
     * Filter admin toolbar
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _filter_admin_toolbar()
    {
        global $wp_admin_bar;

        $service = AAM::api()->admin_toolbar();
        $nodes   = $wp_admin_bar->get_nodes();

        foreach ((is_array($nodes) ? $nodes : []) as $id => $node) {
            if (!$node->group && $service->is_denied($id)) {
                if (!empty($node->parent)) { // update parent node with # link
                    $parent = $wp_admin_bar->get_node($node->parent);

                    if ($parent && ($parent->href === $node->href)) {
                        $wp_admin_bar->add_node(array(
                            'id'   => $parent->id,
                            'href' => '#'
                        ));
                    }
                }

                $wp_admin_bar->remove_node($id);
            }
        }
    }

}