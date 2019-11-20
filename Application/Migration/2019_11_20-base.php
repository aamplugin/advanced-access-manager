<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.1
 */

namespace AAM\Migration;

use WP_Error,
    AAM_Core_API,
    AAM_Core_Config,
    AAM_Core_Migration,
    AAM_Core_ConfigPress,
    AAM_Addon_Repository,
    AAM_Core_AccessSettings,
    AAM_Backend_Feature_Settings_Core,
    AAM_Core_Contract_MigrationInterface,
    AAM_Backend_Feature_Settings_Content,
    AAM_Backend_Feature_Settings_Security;

/**
 * This migration class clears legacy AAM cache options
 *
 * The Migration600 does not take in consideration legacy AAM cache options and this
 * migration script clears them up as well as migration log
 *
 * @package AAM
 * @version 6.0.1
 */
class Migration601 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.0.1
     */
    public function run()
    {
        // Reset failure log
        AAM_Core_Migration::resetFailureLog();

        // Clear any cache that AAM set in the past
        $this->clearInternalCache();

        return array('errors' => array());
    }

    /**
     * Clear internal AAM cache
     *
     * @return void
     *
     * @access protected
     * @version 6.0.1
     */
    protected function clearInternalCache()
    {
        global $wpdb;

        // Delete AAM internal cache from the _options table
        $opt_query  = "DELETE FROM {$wpdb->options} WHERE (`option_name` LIKE %s) ";
        $opt_query .= "OR (`option_name` LIKE %s)";
        $wpdb->query($wpdb->prepare($opt_query, array('aam_cache_%', 'aam_%_cache')));

        // Fetch access settings from the wp_usermeta table
        $query  = "DELETE FROM {$wpdb->usermeta} WHERE (`meta_key` = %s)";
        $wpdb->query($wpdb->prepare($query, array("{$wpdb->prefix}aam_cache")));
    }

}

if (defined('AAM_KEY')) {
    return (new Migration601())->run();
}