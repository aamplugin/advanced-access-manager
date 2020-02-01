<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\Migration;

use AAM_Core_Contract_MigrationInterface;

/**
 * This migration class that fixes potentially corrupted data with aam_addons option
 *
 * @package AAM
 * @version 6.3.1
 */
class Migration631 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.3.1
     */
    public function run()
    {
        $option = \AAM_Core_API::getOption(
            \AAM_Addon_Repository::DB_OPTION, array(), get_main_site_id()
        );

        if (is_string($option)) {
            $option = maybe_unserialize($option);
            \AAM_Core_API::updateOption(
                \AAM_Addon_Repository::DB_OPTION, $option, get_main_site_id()
            );
        }

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration631())->run();
}