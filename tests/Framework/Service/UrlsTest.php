<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "URLs" framework service
 */
final class UrlsTest extends TestCase
{

    /**
     * Testing URL restriction
     *
     * The test will set a restriction to a couple URLs without query params and
     * ensure that access is properly restricted
     *
     * @return void
     */
    public function testUrlRestrictionsWithoutQueryParams() : void
    {
        $service = AAM::api()->default()->urls();

        // Setting access to URL without query params
        $this->assertTrue($service->deny('/url-a'));

        // Verifying that visitors do not have access to the URL
        $visitor_urls = AAM::api()->urls();

        $this->assertTrue($visitor_urls->is_denied('/url-a'));
        $this->assertTrue($visitor_urls->is_denied('/url-a?random-param=2'));
        $this->assertFalse($visitor_urls->is_denied('/url-amber'));
    }

    /**
     * Testing URL restriction
     *
     * The test will set a restriction to a couple URLs with query params and
     * ensure that access is properly restricted
     *
     * @return void
     */
    public function testUrlRestrictionsWithQueryParams() : void
    {
        $service = AAM::api()->default()->urls();

        // Setting access to URL without query params
        $this->assertTrue($service->deny('/url-a?test=a'));

        // Verifying that visitors do not have access to the URL
        $visitor_urls = AAM::api()->urls();

        $this->assertFalse($visitor_urls->is_denied('/url-a'));
        $this->assertTrue($visitor_urls->is_denied('/url-a?test=a'));
        $this->assertTrue($visitor_urls->is_denied('/url-a?test=a&blah=b'));
        $this->assertFalse($visitor_urls->is_denied('/url-amber'));
        $this->assertFalse($visitor_urls->is_denied('/url-a?test=b'));
        $this->assertTrue($visitor_urls->is_denied('/url-a?blah=b&test=a'));
    }

}