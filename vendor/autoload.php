<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

//Composer Semver for Policy dependency versioning
if (!class_exists('Composer\Semver')) {
    spl_autoload_register(function($class_name) {
        if (strpos($class_name, 'Composer\Semver') === 0) {
            $normalized = str_replace(
                array('Composer\Semver', '\\'),
                array('composer', '/'),
                $class_name
            );
            $filename = __DIR__ . '/' . $normalized . '.php';
        }

        if (!empty($filename) && file_exists($filename)) {
            require_once $filename;
        }
    });
}