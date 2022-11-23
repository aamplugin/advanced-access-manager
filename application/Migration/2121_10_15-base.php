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
 * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/236
 * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/198
 * @since 6.8.0 Initial implementation of the class
 *
 * @version 6.9.3
 */
class Migration680 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @since 6.9.3 https://github.com/aamplugin/advanced-access-manager/issues/236
     * @since 6.8.1 https://github.com/aamplugin/advanced-access-manager/issues/198
     * @since 6.8.0 Initial implementation of the method
     *
     * @version 6.9.3
     */
    public function run()
    {
        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration680())->run();
}