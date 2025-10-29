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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Addon_Repository
{

    /**
     * Single instance of itself
     *
     * @var object
     * @access private
     *
     * @version 7.0.0
     */
    private static $_instance = null;

    /**
     * The latest know premium release
     *
     * Note! This is the latest version at the time of AAM publishing
     *
     * @version 7.0.10
     */
    const LATEST_PREMIUM_VERSION = '7.0.7';

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_premium_data()
    {
        // Determining if there is newer version
        $slug       = 'aam-complete-package';
        $version    = $this->get_plugin_version("{$slug}/bootstrap.php");
        $has_update = $this->has_plugin_update("{$slug}/bootstrap.php", $version);

        return array(
            'title'       => 'Advanced Access Manager - Premium Add-On',
            'version'     => $version,
            'hasUpdate'   => $has_update,
            'license'     => $this->get_premium_license_key($slug),
            'description' => __('The complete list of all premium features in one package. All the future features will be available for download for no additional cost as long as the subscription stays active.', 'advanced-access-manager'),
            'url'         => 'https://aamportal.com/premium?ref=plugin'
        );
    }

    /**
     * Get list of all registered licenses
     *
     * @return string|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_premium_license_key()
    {
        $result = null;

        // New way to handle the licensing
        if (defined('AAM_COMPLETE_PACKAGE_LICENSE')) {
            $result = AAM_COMPLETE_PACKAGE_LICENSE;
        }

        return $result;
    }

    /**
     * Check if plugin has new version available
     *
     * @param string $id
     * @param string $current_version
     *
     * @return boolean
     * @access protected
     *
     * @version 7.0.0
     */
    protected function has_plugin_update($id, $current_version)
    {
        $has_update = false;
        $plugins    = get_site_transient('update_plugins');

        if (isset($plugins->response) && is_array($plugins->response)) {
            $has_update = array_key_exists($id, $plugins->response);
        }

        // Also check if current version lower than known
        if ($has_update === false && !empty($current_version)) {
            $has_update = version_compare(
                $current_version, self::LATEST_PREMIUM_VERSION
            ) === -1;
        }

        return $has_update;
    }

    /**
     * Get plugin version
     *
     * @param string $plugin
     *
     * @return string|null
     * @access protected
     *
     * @version 7.0.0
     */
    protected function get_plugin_version($plugin)
    {
        $data    = $this->get_plugin_data($plugin);
        $version = (isset($data['Version']) ? $data['Version'] : null);

        return (!empty($version) ? $version : null);
    }

    /**
     * Get plugin details from the WP core
     *
     * @param string $plugin
     *
     * @return array|null
     * @access protected
     *
     * @version 7.0.0
     */
    protected function get_plugin_data($plugin)
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
     * Bootstrap the object
     *
     * @return AAM_Addon_Repository
     * @access public
     *
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of itself
     *
     * @return AAM_Addon_Repository
     * @access public
     *
     * @version 7.0.0
     */
    public static function get_instance()
    {
        return self::bootstrap();
    }

}