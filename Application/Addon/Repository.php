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
 * @version 6.7.6
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
     * Collection of license violations
     *
     * @version 6.7.5
     */
    const DB_VIOLATION_OPTION = 'aam_violations';

    /**
     * Official list of available addons
     *
     * @version 6.7.5
     */
    const OFFICIAL_ADDON_LIST = array(
        'aam-plus-package' => array(
            'title'       => 'Plus Package',
            'slug'        => 'plus-package',
            'description' => 'Manage access to your WordPress website posts, pages, media, custom post types, categories, tags and custom taxonomies for any role, individual user, visitors or even define default access for everybody; and do this separately for frontend, backend or API levels.',
            'version'     => '5.4.2'
        ),
        'aam-ip-check' => array(
            'title'       => 'IP Check',
            'slug'        => 'ip-check',
            'description' => 'Manage access to your WordPress website by users IP address or referred host and completely lock down the entire website if necessary. Define the unlimited number of whitelisted or blacklisted IPs or hosts.',
            'version'     => '4.1.4'
        ),
        'aam-role-hierarchy' => array(
            'title'       => 'Role Hierarchy',
            'slug'        => 'role-hierarchy',
            'description' => 'Define and manage complex WordPress role hierarchy where all the access settings are propagated down the tree with the ability to override any settings for any specific role.',
            'version'     => '3.0.1'
        ),
        'aam-complete-package' => array(
            'title'       => 'Complete Package',
            'slug'        => 'complete-package',
            'description' => 'Get the complete list of all premium AAM addons in one package and all future premium addons will be included for now additional cost.',
            'version'     => '5.2.8'
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
     * Get license registry
     *
     * @param boolean $license_only
     *
     * @return array
     *
     * @since 6.7.6 https://github.com/aamplugin/advanced-access-manager/issues/177
     * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.3.0 Fixed bug that causes PHP Notice about license index is missing.
     *              Optimized for Multisite setup
     * @since 6.0.5 Added the $license_only argument
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.6
     */
    public function getRegistry()
    {
        static $registry = null;

        if (is_null($registry)) {
            $registry = array();
            $option   = AAM_Core_API::getOption(
                self::DB_OPTION, array(), AAM_Core_API::getMainSiteId()
            );

            // Iterate over the list of official add-ons and check if there is any
            // installed
            foreach(array_keys(self::OFFICIAL_ADDON_LIST) as $id) {
                $plugin = $this->getPluginData("{$id}/bootstrap.php");

                if (!is_null($plugin)) { // Capturing the fact that add-on is installed
                    $registry[$id] = null;
                }

                // Finally merge official addons with respective licenses
                if (!empty($option[$id])) {
                    $registry[$id] = $option[$id];
                }
            }
        }

        return $registry;
    }

    /**
     * Get key/value pair of add-ons
     *
     * @return array
     *
     * @access public
     * @version 6.7.5
     */
    public function getAddonLicenseMap()
    {
        $response = array();

        foreach($this->getRegistry() as $id => $data) {
            $response[$id] = !empty($data['license']) ? $data['license'] : null;
        }

        return $response;
    }

    /**
     * Get list of violations
     *
     * @return array
     *
     * @access public
     * @version 6.7.5
     */
    public function getViolations()
    {
        return AAM_Core_API::getOption(
            self::DB_VIOLATION_OPTION, array(), AAM_Core_API::getMainSiteId()
        );
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
     * Check if there are any violations registered
     *
     * @return boolean
     *
     * @access public
     * @since 6.7.5
     */
    public function hasViolations()
    {
        return count($this->getViolations()) > 0;
    }

    /**
     * Store the license key
     *
     * @param object $package
     * @param string $license
     *
     * @return boolean
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.0.5 Initial implementation of the method
     *
     * @access public
     * @version 6.7.5
     */
    public function registerLicense($package, $license)
    {
        $list = $this->getRegistry();

        $list[$package['slug']] = array(
            'license' => $license, 'expire' => $package['expire']
        );

        // Update the registry
        $result = AAM_Core_API::updateOption(
            self::DB_OPTION, $list, AAM_Core_API::getMainSiteId()
        );

        // If there are any violations, clear them
        $this->removeViolation($package['slug']);

        return $result;
    }

    /**
     * Remove registered violation for a given addon
     *
     * @param string $addon
     *
     * @return void
     *
     * @access public
     * @version 6.7.5
     */
    public function removeViolation($addon)
    {
        $violations = $this->getViolations();

        if (isset($violations[$addon])) {
            unset($violations[$addon]);

            AAM_Core_API::updateOption(
                self::DB_VIOLATION_OPTION, $violations, AAM_Core_API::getMainSiteId()
            );
        }
    }

    /**
     * Register new license violation and process the action if defined
     *
     * @param string $addon
     * @param string $message
     * @param string $action
     *
     * @return void
     *
     * @access public
     * @version 6.7.5
     */
    public function processViolation($addon, $message, $action = null)
    {
        $violations         = $this->getViolations();
        $violations[$addon] = $message;

        // Store the violation for the further displaying
        AAM_Core_API::updateOption(
            self::DB_VIOLATION_OPTION, $violations, AAM_Core_API::getMainSiteId()
        );

        if ($action === 'deactivate') {
            deactivate_plugins("{$addon}/bootstrap.php", true, is_network_admin());
        }
    }

    /**
     * Get list of all addons with detailed information about each
     *
     * @return array
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.4.2 Added https://github.com/aamplugin/advanced-access-manager/issues/88
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.7.5
     */
    public function getList()
    {
        $response = array();

        foreach(self::OFFICIAL_ADDON_LIST as $id => $details) {
            $response[$id] = $this->buildAddonObject(
                $details['title'],
                $details['slug'],
                __($details['description'], AAM_KEY),
                $details['version']
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
     * @param string $version
     *
     * @return array
     *
     * @since 6.4.3 Fixed https://github.com/aamplugin/advanced-access-manager/issues/92
     * @since 6.4.2 Added https://github.com/aamplugin/advanced-access-manager/issues/88
     * @since 6.0.5 Added new `hasUpdate` flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.3
     */
    protected function buildAddonObject($title, $slug, $description, $version = null)
    {
        // Determining if there is newer version
        $current = $this->getPluginVersion("aam-{$slug}/bootstrap.php");

        if (!empty($current) && version_compare($current, $version) === -1) {
            $hasUpdate = true;
        } else {
            $hasUpdate = $this->hasPluginUpdate("aam-{$slug}/bootstrap.php");
        }

        return array(
            'title'       => $title,
            'version'     => $current,
            'isActive'    => $this->isPluginActive("aam-{$slug}/bootstrap.php"),
            'expires'     => $this->getExpirationDate("aam-{$slug}"),
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
     * Check if plugin is active
     *
     * @param string $plugin
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function isPluginActive($plugin)
    {
        $data = $this->getPluginData($plugin);

        if (!empty($data)) {
            $active = is_plugin_active($plugin);
        } else {
            $active = false;
        }

        return $active;
    }

    /**
     * Get license expiration date
     *
     * @param string $plugin
     *
     * @return string|null
     *
     * @since 6.2.0 Fixed bug with PHP notice when `expire` is not defined
     * @since 6.0.0 Initial implementation of the method
     * @since 6.0.5 Fixed typo in the property name
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.2.0
     */
    protected function getExpirationDate($plugin)
    {
        $r = $this->getRegistry();

        return (isset($r[$plugin]['expire']) ? $r[$plugin]['expire'] : null);
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