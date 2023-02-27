<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Core;

use AAM_Core_Jwt_Manager,
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
        $token   = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2NjU0MDQzNjYsImlzcyI6Imh0dHA6Ly9kZXYud29yZHByZXNzIiwiZXhwIjoxNjY1NDA0MzY4LCJqdGkiOiI2MjhlNjM1Mi02ZTY0LTQ4YWEtYTRhOC00MTlkYzgwMDA1YzMiLCJ0ZXN0Ijp0cnVlfQ.FRYXcsBhJOTMj8PDjmge-pf-FSYkwY8OmqIbcS7vVIg';
        $manager = AAM_Core_Jwt_Manager::getInstance();

        $result = $manager->validate($token);

        $this->assertTrue(is_a($result, 'WP_Error'));
        $this->assertEquals($result->get_error_message(), 'Expired token');
    }

}