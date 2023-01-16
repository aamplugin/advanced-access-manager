<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Legacy class
 *
 * This class exists only to cover the fatal error during plugin update from 5.11 to
 * 6.x.x. The problem is with the way AAM registers hooks to WP core `http_response`
 * hook
 *
 * @package AAM
 * @since 6.0.3
 * @todo Remove in 7.0.0
 */
final class AAM_Core_Server
{

    /**
     * Server endpoint
     *
     * @version 6.0.3
     */
    const SERVER_V2_URL = 'https://api.aamportal.com';

    /**
     * Get AAM server endpoint
     *
     * @param string $v
     *
     * @return string
     *
     * @access public
     * @version 6.0.3
     */
    public static function getEndpoint($v = 'V1')
    {
        $endpoint = getenv("AAM_API_{$v}_ENDPOINT");

        if (empty($endpoint)) {
            $endpoint = self::SERVER_V2_URL;
        }

        return $endpoint;
    }

}