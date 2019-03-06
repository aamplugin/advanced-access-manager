<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

// Firebase for JWT token handling
if (!class_exists('Firebase\JWT')) {
    require __DIR__ . '/firebase/BeforeValidException.php';
    require __DIR__ . '/firebase/ExpiredException.php';
    require __DIR__ . '/firebase/SignatureInvalidException.php';
    require __DIR__ . '/firebase/JWT.php';
}

//Composer Semver for Policy dependency versioning
if (!class_exists('Composer\Semver')) {
    /**
     * Load composer semantic version check
     * 
     * @param string $classname
     * 
     * @return void
     */
    function loadComposerSemver($classname) {
        if (strpos($classname, 'Composer\Semver') === 0) {
            $normalized = str_replace(
                array('Composer\Semver', '\\'), 
                array('composer', '/'), 
                $classname
            );
            $filename = __DIR__ . '/' . $normalized . '.php';
        }

        if (!empty($filename) && file_exists($filename)) {
            require $filename;
        }
    }
    spl_autoload_register('loadComposerSemver');
}