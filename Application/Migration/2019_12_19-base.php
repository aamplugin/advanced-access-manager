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
 * This migration class that just clears all the errors
 *
 * @since 6.4.0 Fixed bug with unsaved migration
 * @since 6.2.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.4.0
 */
class Migration620 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @since 6.4.0 Fixed bug with unsaved migration
     * @since 6.2.0 Initial implementation of the method
     *
     * @version 6.4.0
     */
    public function run()
    {
        // Reset failure log
        AAM_Core_Migration::resetFailureLog();

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration620())->run();
}