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
 * @version 6.9.5
 */
class AAM_Addon_Repository
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * DB options name
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_addons';

    /**
     * Official list of available addons
     *
     * @since 6.9.5 https://github.com/aamplugin/advanced-access-manager/issues/243
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
     * @since 6.7.5 Initial implementation of the constant
     *
     * @version 6.9.5
     */
    const OFFICIAL_ADDON_LIST = array(
        'aam-complete-package' => array(
            'title'       => 'Complete Package',
            'slug'        => 'complete-package',
            'description' => 'The complete list of all premium AAM features in one package. All the future features will be available for download for no additional cost as long as the subscription stays active.',
            'version'     => '5.3.0'
        ),
    );

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
     * Get list of all registered licenses
     *
     * @return array
     *
     * @access public
     *
     * @version 6.9.3
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
     * Check if there is at least one license registered
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function hasRegistry()
    {
        return count($this->getRegistry()) > 0;
    }

    /**
     * Reset registry
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.3
     */
    public function resetRegistry()
    {
        return AAM_Core_API::deleteOption(
            self::DB_OPTION, AAM_Core_API::getMainSiteId()
        );
    }

    /**
     * Store the license key
     *
     * @param object $package
     * @param string $license
     *
     * @return boolean
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.0.5 Initial implementation of the method
     *
     * @access public
     *
     * @version 6.9.3
     */
    public function registerLicense($package, $license)
    {
        $list = $this->getRegistry();

        $list[$package['slug']] = array(
            'license' => $license
        );

        // Update the registry
        $result = AAM_Core_API::updateOption(
            self::DB_OPTION, $list, AAM_Core_API::getMainSiteId()
        );

        return $result;
    }

    /**
     * Get list of all addons with detailed information about each
     *
     * @return array
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/88
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.3
     */
    public function getList()
    {
        $response = array();

        foreach(self::OFFICIAL_ADDON_LIST as $id => $details) {
            $response[$id] = $this->buildAddonObject(
                $details['title'],
                $details['slug'],
                __($details['description'], AAM_KEY)
            );
        }

        return $response;
    }

    /**
     * Build add-on data model
     *
     * @param string $title
     * @param string $slug
     * @param string $description
     *
     * @return array
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/237
     * @since 6.4.3 https://github.com/aamplugin/advanced-access-manager/issues/92
     * @since 6.4.2 https://github.com/aamplugin/advanced-access-manager/issues/88
     * @since 6.0.5 Added new `hasUpdate` flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.3
     */
    protected function buildAddonObject($title, $slug, $description)
    {
        // Determining if there is newer version
        $current   = $this->getPluginVersion("aam-{$slug}/bootstrap.php");
        $hasUpdate = $this->hasPluginUpdate("aam-{$slug}/bootstrap.php");

        return array(
            'title'       => $title,
            'version'     => $current,
            'hasUpdate'   => $hasUpdate,
            'license'     => $this->getPluginLicense("aam-{$slug}"),
            'type'        => 'commercial',
            'description' => $description,
            'url'         => 'https://aamplugin.com/pricing/' . $slug
        );
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