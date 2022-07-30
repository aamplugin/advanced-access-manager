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
 * Migrating 404 redirect rules to use 404 Redirect object. For more details refer
 * to the https://github.com/aamplugin/advanced-access-manager/issues/64
 *
 * @package AAM
 * @version 6.4.0
 */
class Migration640 implements AAM_Core_Contract_MigrationInterface
{

    /**
     * @inheritdoc
     *
     * @version 6.4.0
     */
    public function run()
    {
        $option = \AAM_Core_API::getOption(
            \AAM_Core_Config::DB_OPTION, array()
        );

        // Check if there are any 404 redirect access settings defined
        if (isset($option['frontend.404redirect.type'])) {
            $type     = $option['frontend.404redirect.type'];
            $redirect = $option["frontend.404redirect.{$type}"];

            $object = \AAM::api()->getDefault()->getObject(
                \AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
            );
            $object->setExplicitOption(array(
                '404.redirect.type'    => $type,
                "404.redirect.{$type}" => $redirect
            ));

            $object->save();

            //if ($object->save()) {
                //\AAM_Core_Config::delete('frontend.404redirect.type');
                //\AAM_Core_Config::delete("frontend.404redirect.{$type}");
            //}
        }

        // Finally store this script as completed
        AAM_Core_Migration::storeCompletedScript(basename(__FILE__));

        return array('errors' => array());
    }

}

if (defined('AAM_KEY')) {
    return (new Migration640())->run();
}