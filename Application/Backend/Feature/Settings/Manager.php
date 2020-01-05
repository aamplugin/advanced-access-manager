<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Settings area abstract manager
 *
 * @since 6.2.0 Added Import/Export functionality
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.0
 */
class AAM_Backend_Feature_Settings_Manager extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the settings tab
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * Save the option
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $param = $this->getFromPost('param');
        $value = $this->getFromPost('value');

        AAM_Core_Config::set($param, $value);

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Clear all AAM settings
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function clearSettings()
    {
        AAM_Core_API::clearSettings();

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Aggregate support request metadata
     *
     * @return string
     *
     * @access public
     * @version 6.2.0
     */
    public function getSupportMetadata()
    {
        global $wp_version;

        return wp_json_encode(array(
            'phpVersion' => PHP_VERSION,
            'wpVersion'  => $wp_version,
            'aamVersion' => AAM_VERSION,
            'settings'   => AAM_Core_API::getOption(
                AAM_Core_AccessSettings::DB_OPTION, array()
            ),
            'config'     => AAM_Core_API::getOption(
                AAM_Core_Config::DB_OPTION, array()
            ),
            'configpress' => AAM_Core_API::getOption(
                AAM_Core_ConfigPress::DB_OPTION, array()
            ),
            'roles'      => AAM_Core_API::getOption(
                AAM_Core_API::getRoles()->role_key, array()
            ),
            'addons'     => AAM_Addon_Repository::getInstance()->getRegistry(),
            'plugins'    => get_plugins()
        ));
    }

    /**
     * Export AAM settings as JSON
     *
     * @return string
     *
     * @access public
     * @version 6.2.0
     */
    public function exportSettings()
    {
        $data = array(
            'version'   => AAM_VERSION,
            'plugin'    => AAM_KEY,
            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('U'),
            'dataset'   => array()
        );

        $groups = AAM::api()->getConfig(
            'core.export.groups', array('settings', 'config', 'roles')
        );

        if (is_string($groups)) {
            $groups = array_map('trim', explode(',', $groups));
        }

        foreach($groups as $group) {
            switch($group) {
                case 'settings':
                    $data['dataset']['settings'] = AAM_Core_API::getOption(
                        AAM_Core_AccessSettings::DB_OPTION, array()
                    );
                    break;

                case 'config':
                    $data['dataset']['config'] = AAM_Core_API::getOption(
                        AAM_Core_Config::DB_OPTION, array()
                    );
                    $data['dataset']['configpress'] = AAM_Core_API::getOption(
                        AAM_Core_ConfigPress::DB_OPTION, array()
                    );
                    break;

                case 'roles':
                    $data['dataset']['roles'] = AAM_Core_API::getOption(
                        AAM_Core_API::getRoles()->role_key, array()
                    );
                    break;

                default:
                    break;
            }
        }

        return wp_json_encode(array(
            'result' => base64_encode(wp_json_encode($data))
        ));
    }

    /**
     * Import AAM settings
     *
     * @return string
     *
     * @access public
     * @version 6.2.0
     */
    public function importSettings()
    {
        $error = __('Invalid data', AAM_KEY);
        $data  = json_decode($this->getFromPost('payload'), true);

        if ($data) {
            if (isset($data['dataset']) && is_array($data['dataset'])) {
                foreach($data['dataset'] as $group => $settings) {
                    switch($group) {
                        case 'settings':
                            AAM_Core_API::updateOption(
                                AAM_Core_AccessSettings::DB_OPTION, $settings
                            );
                            break;

                        case 'config':
                            AAM_Core_API::updateOption(
                                AAM_Core_Config::DB_OPTION, $settings
                            );
                            break;

                        case 'roles':
                            AAM_Core_API::updateOption(
                                AAM_Core_API::getRoles()->role_key, $settings
                            );
                            break;

                        default:
                            break;
                    }
                }
                $error = null;
            }
        }

        if ($error !== null) {
            $response = array('status' => 'failure', 'reason' => $error);
        } else {
            $response = array('status' => 'success');
        }

        return wp_json_encode($response);
    }

    /**
     * Register settings UI manager
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
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