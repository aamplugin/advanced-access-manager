<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Libs;

/**
 * Mocking HTTP headers
 *
 * @package AAM\UnitTest
 * @version 6.7.8
 */
trait HeaderTrait
{

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function getAllHeaders()
    {
        // Get all unique HTTP headers. Any later headers, override the previous
        // headers
        $unique = array();

        foreach($GLOBALS['UT_HTTP_HEADERS'] as $header) {
            $split = explode(':', $header);

            $unique[$split[0]] = $header;
        }

        return array_values($unique);
    }

    /**
     * Undocumented function
     *
     * @param [type] $header
     * @return void
     */
    protected function setHeader($header)
    {
        array_push($GLOBALS['UT_HTTP_HEADERS'], $header);
    }

}