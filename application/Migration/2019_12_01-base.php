<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\Migration;

use AAM_Core_API,
    AAM_Core_Migration,
    AAM_Addon_Repository,
    AAM_Core_Contract_MigrationInterface;

/**
 * This migration class that converts add-ons registry
 *
 * @since 6.2.0 Simplified script
 * @since 6.1.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.2.0
 */
class Migration610 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @since 6.3.0 Optimized for Multisite setup
     * @since 6.2.0 Removed failure log clean-up. Delegating this to the latest
     *              migration script
     * @since 6.1.0 Initial implementation of the method
     *
     * @version 6.3.0
     */
    public function run()
    {
        $list = AAM_Core_API::getOption(AAM_Addon_Repository::DB_OPTION);

        if (is_array($list)) {
            $converted = array();

            foreach($list as $slug => $data) {
                if (stripos($slug, 'plus') !== false) {
                    $converted['aam-plus-package'] = $data;
                } elseif (stripos($slug, 'hierarchy') !== false) {
                    $converted['aam-role-hierarchy'] = $data;
                } elseif (stripos($slug, 'check') !== false) {
                    $converted['aam-ip-check'] = $data;
                } elseif (stripos($slug, 'complete') !== false) {
                    $converted['aam-complete-package'] = $data;
                } elseif (stripos($slug, 'commerce') !== false) {
                    $converted['aam-ecommerce'] = $data;
                }
            }

            AAM_Core_API::updateOption(AAM_Addon_Repository::DB_OPTION, $converted);
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration610())->run();
}