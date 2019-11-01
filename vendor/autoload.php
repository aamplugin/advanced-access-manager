<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

// Firebase for JWT token handling
if (!class_exists('Firebase\JWT')) {
    spl_autoload_register(function($class_name) {
        if (strpos($class_name, 'Firebase\JWT') === 0) {
            require __DIR__ . '/firebase/BeforeValidException.php';
            require __DIR__ . '/firebase/ExpiredException.php';
            require __DIR__ . '/firebase/SignatureInvalidException.php';
            require __DIR__ . '/firebase/JWT.php';
        }
    });
}

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
            require $filename;
        }
    });
}