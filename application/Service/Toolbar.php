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
 * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/223
 * @since 6.4.0 https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.0
 */
class AAM_Service_Toolbar
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;


    /**
     * DB option name for cache
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_toolbar_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.toolbar.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Toolbar::register();
                });

                //admin toolbar
                if (AAM::isAAM()) {
                    add_action('wp_after_admin_bar_render', array($this, 'cacheAdminBar'));
                }
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Admin Toolbar', AAM_KEY),
                    'description' => __('Manage access to the top admin toolbar items for any role or individual user. The service only removes restricted items but does not actually protect from direct access via link.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 10);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Cache admin tool bar
     *
     * This is done so the complete list of admin toolbar items can be displayed on
     * the AAM UI page
     *
     * @return void
     *
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/223
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @global object $wp_admin_bar
     * @version 6.9.0
     */
    public function cacheAdminBar()
    {
        global $wp_admin_bar;

        $reflection = new ReflectionClass(get_class($wp_admin_bar));
        $cache      = array();

        if ($reflection->hasProperty('nodes')) {
            $prop = $reflection->getProperty('nodes');
            $prop->setAccessible(true);

            $nodes = $prop->getValue($wp_admin_bar);

            if (isset($nodes['root'])) {
                foreach ($nodes['root']->children as $node) {
                    $cache = array_merge($cache, $node->children);
                }

                // do some cleanup
                foreach ($cache as $i => $node) {
                    if ($node->id === 'menu-toggle') {
                        unset($cache[$i]);
                    }
                }
                AAM_Core_API::updateOption(self::DB_OPTION, $cache);
            }
        }

        return $cache;
    }

    /**
     * Get cached admin toolbar
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getToolbarCache()
    {
        return AAM_Core_API::getOption(self::DB_OPTION);
    }

    /**
     * Initialize Admin Toolbar hooks
     *
     * @return void
     *
     * @since 6.4.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.0
     */
    protected function initializeHooks()
    {
        if ($this->getFromQuery('init') !== 'toolbar' && !AAM::isAAM()) {
            add_action(
                'wp_before_admin_bar_render',
                function () {
                    global $wp_admin_bar;

                    $toolbar = AAM::getUser()->getObject('toolbar');
                    $nodes   = $wp_admin_bar->get_nodes();

                    foreach ((is_array($nodes) ? $nodes : array()) as $id => $node) {
                        if ($toolbar->isHidden($id, true)) {
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
                },
                PHP_INT_MAX
            );
        }

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        );

        add_action('aam_clear_settings_action', function() {
            AAM_Core_API::deleteOption(self::DB_OPTION);
        });
    }

    /**
     * Generate Toolbar policy statements
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
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_Toolbar::OBJECT_TYPE) {
            if (!empty($options)) {
                $policy['Statement'] = array_merge(
                    $policy['Statement'],
                    $generator->generateBasicStatements($options, 'Toolbar')
                );
            }
        }

        return $policy;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Toolbar::bootstrap();
}