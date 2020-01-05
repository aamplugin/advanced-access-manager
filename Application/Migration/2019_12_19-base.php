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
 * @package AAM
 * @version 6.2.0
 */
class Migration620 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.2.0
     */
    public function run()
    {
        // Reset failure log
        AAM_Core_Migration::resetFailureLog();

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration620())->run();
}