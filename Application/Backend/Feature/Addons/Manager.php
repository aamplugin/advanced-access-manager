<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Add-on manager
 *
 * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
 * @since 6.0.5 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.5
 */
class AAM_Backend_Feature_Addons_Manager extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the Add-ons area
     *
     * @version 6.0.5
     */
    const ACCESS_CAPABILITY = 'aam_manage_addons';

    /**
     * Register AAM license
     *
     * @return string
     *
     * @access public
     * @version 6.0.5
     */
    public function registerLicense()
    {
        $license = $this->getFromPost('license');
        $slug    = $this->getFromPost('slug');
        $expire  = $this->getFromPost('expire');

        $result  = AAM_Addon_Repository::getInstance()->registerLicense(
            array('slug' => $slug, 'expire' => $expire), $license
        );

        return wp_json_encode(array('status' => ($result ? 'success' : 'failure')));
    }

    /**
     * Get internal registry of add-ons
     *
     * This is used to manually check for the updates on the Add-Ons area
     *
     * @return string
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.0.5 Initial version of the method
     *
     * @access public
     * @version 6.7.5
     */
    public function getRegistry()
    {
        return wp_json_encode(
            AAM_Addon_Repository::getInstance()->getAddonLicenseMap()
        );
    }

    /**
     * Update site option about plugin's status
     *
     * @return string
     *
     * @since 6.7.5 https://github.com/aamplugin/advanced-access-manager/issues/173
     * @since 6.0.5 Initial implementation of the method
     *
     * @access public
     * @version 6.7.5
     */
    public function checkForPluginUpdates()
    {
        $current = get_site_transient('update_plugins');
        $payload = json_decode($this->getFromPost('payload'));

        foreach($payload->products as $data) {
            if (isset($current->checked)
                            && array_key_exists($data->plugin, $current->checked)) {
                $current_v = $current->checked[$data->plugin];

                if (version_compare($current_v, $data->new_version) === -1) {
                    $current->response[$data->plugin] = $data;
                    unset($current->no_update[$data->plugin]);
                }

                if (!empty($data->violation)) {
                    AAM_Addon_Repository::getInstance()->processViolation(
                        $data->slug,
                        $data->violation,
                        (isset($data->action) ? $data->action : null)
                    );
                }
            }
        }

        set_site_transient('update_plugins', $current);

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Register add-ons UI manager
     *
     * @return void
     *
     * @access public
     * @version 6.0.5
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'core',
            'view'       => __CLASS__
        ));
    }

}