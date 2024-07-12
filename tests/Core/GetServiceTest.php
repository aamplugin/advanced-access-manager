<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Core;

use PHPUnit\Framework\TestCase;

/**
 * Test the ability to fetch service
 *
 * @package AAM\UnitTest
 * @version 6.4.0
 */
class GetServiceTest extends TestCase
{

    /**
     * Test that all available for fetching service are retrieved
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testServiceFetch()
    {
        $this->assertEquals(
            'AAM_Service_AccessPolicy',
            get_class(apply_filters('aam_get_service_filter', null, 'access-policy'))
        );

        $this->assertEquals(
            'AAM_Service_AdminMenu',
            get_class(apply_filters('aam_get_service_filter', null, 'admin-menu'))
        );

        $this->assertEquals(
            'AAM_Service_Content',
            get_class(apply_filters('aam_get_service_filter', null, 'content'))
        );

        $this->assertEquals(
            'AAM_Service_AccessDeniedRedirect',
            get_class(apply_filters('aam_get_service_filter', null, 'access-denied-redirect'))
        );

        $this->assertEquals(
            'AAM_Service_Jwt',
            get_class(apply_filters('aam_get_service_filter', null, 'jwt'))
        );

        $this->assertEquals(
            'AAM_Service_Route',
            get_class(apply_filters('aam_get_service_filter', null, 'api-route'))
        );

        $this->assertEquals(
            'AAM_Service_SecureLogin',
            get_class(apply_filters('aam_get_service_filter', null, 'secure-login'))
        );

        $this->assertNull(
            apply_filters('aam_get_service_filter', null, 'testing')
        );
    }

}