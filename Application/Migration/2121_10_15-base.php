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
 * Clearing the AAM violations repository to remove corrupted data
 *
 * @package AAM
 *
 * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/198
 * @since 6.8.0 Initial implementation of the class
 *
 * @version 6.8.1
 */
class Migration680 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/198
     * @since 6.8.0 Initial implementation of the method
     *
     * @version 6.8.1
     */
    public function run()
    {
        if (defined('\AAM_Addon_Repository::DB_VIOLATION_OPTION')) {
            \AAM_Core_API::deleteOption(\AAM_Addon_Repository::DB_VIOLATION_OPTION);
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration680())->run();
}