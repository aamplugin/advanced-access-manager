<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Addon repository
 *
 * @since 6.9.6 https://github.com/aamplugin/advanced-access-manager/issues/255
 * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/243
 * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
 * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/177
 * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.4.3 https://github.com/aamplugin/advanced-access-manager/issues/92
 * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/88
 * @since 6.4.1 https://github.com/aamplugin/advanced-access-manager/issues/81
 * @since 6.2.0 Bug fixing that is related to unwanted PHP notices
 * @since 6.0.5 Refactored the license managements. Fixed couple bugs with license
 *              information displaying
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.6
 */
class AAM_Addon_Repository
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * DB options name
     *
     * @version 6.0.0
     * @deprecated
     * @todo Remove in the end of 2023
     */
    const DB_OPTION = 'aam_addons';

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
        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Get premium data
     *
     * @return array
     *
     * @access public
     * @version 6.9.6
     */
    public function getPremiumData()
    {
        // Determining if there is newer version
        $slug      = 'aam-complete-package';
        $version   = $this->getPluginVersion("{$slug}/bootstrap.php");
        $hasUpdate = $this->hasPluginUpdate("{$slug}/bootstrap.php");

        return array(
            'title'       => 'AAM Complete Package',
            'version'     => $version,
            'hasUpdate'   => $hasUpdate,
            'license'     => $this->getPluginLicense($slug),
            'description' => __('The complete list of all premium features in one package. All the future features will be available for download for no additional cost as long as the subscription stays active.', AAM_KEY),
            'url'         => 'https://aamportal.com/premium'
        );
    }

    /**
     * Get list of all registered licenses
     *
     * @return array
     *
     * @since 6.9.6 https://github.com/aamplugin/advanced-access-manager/issues/255
     * @since 6.9.3 Initial implementation of the method
     *
     * @access public
     * @version 6.9.6
     * @todo Remove support of "registry"
     */
    public function getRegisteredLicenseList()
    {
        $response = array();
        $registry = $this->getRegistry();

        foreach($registry as $v) {
            if (isset($v['license'])) {
                array_push($response, $v['license']);
            }
        }

        // New way to handle the licensing
        if (defined('AAM_COMPLETE_PACKAGE_LICENSE')
            && !in_array(AAM_COMPLETE_PACKAGE_LICENSE, $response, true)) {
                array_push($response, AAM_COMPLETE_PACKAGE_LICENSE);
        }

        return $response;
    }

    /**
     * Get license registry
     *
     * @param boolean $license_only
     *
     * @return array
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
     * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/177
     * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.3.0 Fixed bug that causes PHP Notice about license index is missing.
     *              Optimized for Multisite setup
     * @since 6.0.5 Added the $license_only argument
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.3
     */
    public function getRegistry()
    {
        $response = array();
        $registry = AAM_Core_API::getOption(
            self::DB_OPTION, array(), AAM_Core_API::getMainSiteId()
        );

        if (is_array($registry)) {
            foreach($registry as $id => $data) {
                if (!empty($data['license'])) {
                    $response[$id] = $data;
                }
            }
        }

        return $response;
    }

    /**
     * Check if plugin has new version available
     *
     * @param string $id
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.5
     */
    protected function hasPluginUpdate($id)
    {
        $has_update = false;
        $plugins    = get_site_transient('update_plugins');

        if (isset($plugins->response) && is_array($plugins->response)) {
            $has_update = array_key_exists($id, $plugins->response);
        }

        return $has_update;
    }

    /**
     * Get plugin version
     *
     * @param string $plugin
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function getPluginVersion($plugin)
    {
        $data    = $this->getPluginData($plugin);
        $version = (isset($data['Version']) ? $data['Version'] : null);

        return (!empty($version) ? $version : null);
    }

    /**
     * Get plugin details from the WP core
     *
     * @param string $plugin
     *
     * @return array|null
     *
     * @access protected
     * @version 6.0.0
     */
    protected function getPluginData($plugin)
    {
        $filename = WP_PLUGIN_DIR . '/' . $plugin;

        if (function_exists('get_plugin_data') && file_exists($filename)) {
            $data = get_plugin_data($filename);
        } else {
            $data = null;
        }

        return $data;
    }

    /**
     * Get plugin license key
     *
     * @param string $plugin
     *
     * @return string|null
     *
     * @since 6.2.0 Fixed bug with PHP notice when `license` is not defined
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.0
     */
    protected function getPluginLicense($plugin)
    {
        $r = $this->getRegistry();

        return (isset($r[$plugin]['license']) ? $r[$plugin]['license'] : null);
    }

}