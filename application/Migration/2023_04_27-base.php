<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\Migration;

use AAM_Core_Config,
    AAM_Core_Migration,
    AAM_Service_SecureLogin,
    AAM_Core_Contract_MigrationInterface;

/**
 * Moving the user_status "locked" to the user meta
 *
 * @package AAM
 * @version 6.9.10
 */
class Migration6910 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.9.10
     */
    public function run()
    {
        global $wpdb;

        if (AAM_Core_Config::get(AAM_Service_SecureLogin::FEATURE_FLAG, true)) {
            // Get the list of all locked users
            $results = $wpdb->get_results(
                sprintf('SELECT `ID` FROM %s WHERE user_status = 1', $wpdb->users)
            );

            foreach($results as $r) {
                add_user_meta($r->ID, 'aam_user_status', 'locked');
            }
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration6910())->run();
}