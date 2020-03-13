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
 * This migration class that fixes potentially corrupted data with aam_addons option
 *
 * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
 * @since 6.4.0 Fixed bug with unsaved migration
 * @since 6.3.1 Initial implementation of the method
 *
 * @package AAM
 * @version 6.4.2
 */
class Migration631 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @since 6.4.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/81
     * @since 6.4.0 Fixed bug with unsaved migration
     * @since 6.3.1 Initial implementation of the method
     *
     * @version 6.4.2
     */
    public function run()
    {
        $option = \AAM_Core_API::getOption(
            \AAM_Addon_Repository::DB_OPTION,
            array(),
            \AAM_Core_API::getMainSiteId()
        );

        if (is_string($option)) {
            $option = maybe_unserialize($option);
            \AAM_Core_API::updateOption(
                \AAM_Addon_Repository::DB_OPTION,
                $option,
                \AAM_Core_API::getMainSiteId()
            );
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration631())->run();
}