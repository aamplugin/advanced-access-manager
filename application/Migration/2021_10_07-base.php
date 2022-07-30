<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\Migration;

use AAM_Core_Migration,
    AAM_Core_Contract_MigrationInterface;

/**
 * Disabling the "User Role Filter" service by default for any new AAM installation.
 * However, keeping it enabled for currently running AAM instances.
 *
 * @package AAM
 * @version 6.7.9
 */
class Migration679 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.7.9
     */
    public function run()
    {
        // Checking if the "aam_menu_cache" exists and if so, user was at least once
        // on the AAM page, so it is not new installation
        $cache = \AAM_Service_AdminMenu::getInstance()->getMenuCache();

        if (!empty($cache)) {
            \AAM_Core_Config::set('core.service.user-level-filter.enabled', true);
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration679())->run();
}