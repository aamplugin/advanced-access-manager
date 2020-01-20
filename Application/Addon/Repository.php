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
 * @since 6.2.0 Bug fixing that is related to unwanted PHP notices
 * @since 6.0.5 Refactored the license managements. Fixed couple bugs with license
 *              information displaying
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.0
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
     * @since 6.3.0 Fixed bug that causes PHP Notice about license index is missing.
     *              Optimized for Multisite setup
     * @since 6.0.5 Added the $license_only argument
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.0
     */
    public function getRegistry($license_only = false)
    {
        $response = array();
        $registry = AAM_Core_API::getOption(
            self::DB_OPTION, array(), get_main_site_id()
        );

        if ($license_only === true) {
            foreach($registry as $slug => $data) {
                if (!empty($data['license'])) {
                    $response[$slug] = $data['license'];
                }
            }
        } else {
            $response = $registry;
        }

        return (is_array($response) ? $response : array());
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
     * Store the license key
     *
     * @param object $package
     * @param string $license
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.5
     */
    public function registerLicense($package, $license)
    {
        $list = $this->getRegistry();

        $list[$package['slug']] = array(
            'license' => $license, 'expire' => $package['expire']
        );

        // Update the registry
        return AAM_Core_API::updateOption(self::DB_OPTION, $list, get_main_site_id());
    }

    /**
     * Get list of all addons with detailed information about each
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getList()
    {
        return array(
            'aam-plus-package' => $this->buildAddonObject(
                'Plus Package',
                'plus-package',
                __('Manage access to your WordPress website posts, pages, media, custom post types, categories, tags and custom taxonomies for any role, individual user, visitors or even define default access for everybody; and do this separately for frontend, backend or API levels.', AAM_KEY)
            ),
            'aam-ip-check' => $this->buildAddonObject(
                'IP Check',
                'ip-check',
                __('Manage access to your WordPress website by users IP address or referred host and completely lock down the entire website if necessary. Define the unlimited number of whitelisted or blacklisted IPs or hosts.', AAM_KEY)
            ),
            'aam-role-hierarchy' => $this->buildAddonObject(
                'Role Hierarchy',
                'role-hierarchy',
                __('Define and manage complex WordPress role hierarchy where all the access settings are propagated down the tree with the ability to override any settings for any specific role.', AAM_KEY)
            ),
            /**
             * TODO: Release this extension after AAM 6.0.0. Enhance it with
             * subscription functionality and possibly with email notification
             * integration
            'aam-ecommerce' => $this->buildAddonObject(
                'E-Commerce',
                'ecommerce',
                __('Start monetizing access to your premium content. Restrict access to read any WordPress post, page or custom post type until user purchase access to it.', AAM_KEY)
            ),
            */
            'aam-complete-package' => $this->buildAddonObject(
                'Complete Package',
                'complete-package',
                __('Get the complete list of all premium AAM addons in one package and all future premium addons will be included for now additional cost.', AAM_KEY)
            )
        );
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
     * @since 6.0.5 Added new `hasUpdate` flag
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.0.5
     */
    protected function buildAddonObject($title, $slug, $description)
    {
        return array(
            'title'       => $title,
            'version'     => $this->getPluginVersion("aam-{$slug}/bootstrap.php"),
            'isActive'    => $this->isPluginActive("aam-{$slug}/bootstrap.php"),
            'expires'     => $this->getExpirationDate("aam-{$slug}"),
            'hasUpdate'   => $this->hasPluginUpdate("aam-{$slug}/bootstrap.php"),
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
        $data = self::getPluginData($plugin);

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