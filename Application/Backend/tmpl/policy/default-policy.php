<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

global $wp_version;

return sprintf('{
    "Version": "1.0.0",
    "Dependency": {
        "wordpress": ">=%s",
        "advanced-access-manager": ">=%s"
    },
    "Statement": [
        {
            "Effect": "deny",
            "Resource": [],
            "Action": []
        }
    ]
}', $wp_version, AAM_VERSION);