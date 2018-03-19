<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

if (!class_exists('Firebase\JWT')) {
    require __DIR__ . '/firebase/BeforeValidException.php';
    require __DIR__ . '/firebase/ExpiredException.php';
    require __DIR__ . '/firebase/SignatureInvalidException.php';
    require __DIR__ . '/firebase/JWT.php';
}