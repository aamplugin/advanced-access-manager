<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Core;

use AAM_Service_Jwt,
    AAM_Core_Jwt_Manager,
    PHPUnit\Framework\TestCase;

/**
 * Test the core JWT manager
 *
 * @package AAM\UnitTest
 * @version 6.9.0
 */
class JwtManagerTest extends TestCase
{

    /**
     * Test that all available for fetching service are retrieved
     *
     * @return void
     *
     * @access public
     * @version 6.9.0
     */
    public function testTokenCreation()
    {
        $manager = AAM_Core_Jwt_Manager::getInstance();

        $result = $manager->encode(array('test' => true));

        $this->assertIsString($result->token);

        $payload = $manager->validate($result->token);

        $this->assertObjectHasAttribute('test', $payload);
        $this->assertObjectHasAttribute('iat', $payload);
        $this->assertObjectHasAttribute('exp', $payload);
        $this->assertObjectHasAttribute('iss', $payload);
        $this->assertObjectHasAttribute('jti', $payload);
        $this->assertTrue($payload->test);
    }

    /**
     * Test that expired token is properly rejected
     *
     * @return void
     *
     * @access public
     * @version 6.9.0
     */
    public function testExpiredToken()
    {
        $manager = AAM_Core_Jwt_Manager::getInstance();
        $service = AAM_Service_Jwt::getInstance();

        // Let's issue a new token, pause for a second and make sure it is expired
        $res = $service->issueToken(
            AAM_UNITTEST_ADMIN_USER_ID,
            false,
            new \DateTime('@' . (time() + 1), new \DateTimeZone('UTC'))
        );

        sleep(2);

        $result = $manager->validate($res->token);

        $this->assertTrue(is_a($result, 'WP_Error'));
        $this->assertEquals($result->get_error_message(), 'Expired token');
    }

}