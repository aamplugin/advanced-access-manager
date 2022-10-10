<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Jwt;

use DateTime,
    AAM_Service_Jwt,
    WP_REST_Request,
    AAM_Core_Config,
    AAM_Core_Jwt_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Jwt service tests
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class JwtTest extends TestCase
{
    use ResetTrait;

    /**
     * Assert that jwt token is generated for the authentication request
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAuthResponseContainsJwt()
    {
        $server = rest_get_server();

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);
        $request->set_param('issueJWT', true);

        $data = $server->dispatch($request)->get_data();

        $this->assertArrayHasKey('jwt', $data);
    }

    /**
     * Validate that issued JWT token is valid when it is marked as none-revokable
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testValidateNotRevocableJwtToken()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $jwt = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => false,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Validate that issued JWT is not valid when it is marked as revokable and is
     * not stored in the JWT store
     *
     * @access public
     * @version 6.0.0
     */
    public function testValidateRevocableJwtToken()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $jwt = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(410, $response->get_status());
        $this->assertEquals('Token has been revoked', $response->get_data()['reason']);
    }

    /**
     * Validate that JWT token is invalid when it is expired
     *
     * @access public
     * @version 6.0.0
     */
    public function testValidateExpiredJwtToken()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $jwt = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => false,
            'exp'         => DateTime::createFromFormat('m/d/Y', '01/01/2018')->getTimestamp()
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('rest_jwt_validation_failure', $response->get_data()['code']);
        $this->assertEquals('Expired token', $response->get_data()['reason']);
    }

    /**
     * Verify that user JWT token registry is populated correctly
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRegistryPopulated()
    {
        $service = AAM_Service_Jwt::getInstance();
        $tokens  = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        // Assert that the registry is empty
        $this->assertEquals(0, count($tokens));

        // Issue new token and verify that registry increased by one
        $res = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Reset cache
        wp_cache_flush();

        $tokens = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        // Assert that the new token is there
        $this->assertEquals(1, count($tokens));
        $this->assertTrue(in_array($res, $tokens, true));
    }

    /**
     * Verify that registry implement ring-buffer approach and does not allow to
     * overload the DB
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRegistryOverflow()
    {
        AAM_Core_Config::set('authentication.jwt.registryLimit', 1);

        // Reset cache
        wp_cache_flush();

        $service = AAM_Service_Jwt::getInstance();
        $tokens  = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        // Assert that the registry is empty
        $this->assertEquals(0, count($tokens));

        // Issue new token and verify that registry increased by one
        $res1 = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Reset cache
        wp_cache_flush();

        $tokens = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        // Assert that token is in the registry
        $this->assertEquals(1, count($tokens));

        // Issue a new token and make sure that there is only one token in the
        // registry
        $res2 = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Reset cache
        wp_cache_flush();

        $tokens = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        // Assert that token is in the registry
        $this->assertEquals(1, count($tokens));

        $this->assertFalse(in_array($res1, $tokens, true));
        $this->assertTrue(in_array($res2, $tokens, true));
    }

    /**
     * Verify that token can be refreshed successfully
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRefreshValid()
    {
        $server  = rest_get_server();
        $service = AAM_Service_Jwt::getInstance();

        // Issue a token that later we'll refresh
        $jwt = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID, null, null, true);

        // Refresh token
        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Verify that token can't be refreshed if it is simply invalid JWT token
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRefreshNotValid()
    {
        $server = rest_get_server();

        // Refresh token
        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', 'invalid-token');

        $response = $server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertStringContainsString('Wrong number of segments', $response->get_data()['reason']);
    }

    /**
     * Verify that new token is not issued for already expired token
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRefreshExpired()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $jwt = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => true,
            'exp'         => DateTime::createFromFormat('m/d/Y', '01/01/2018')->getTimestamp()
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('Expired token', $response->get_data()['reason']);
    }

    /**
     * Verify that new token is not issued for none-refreshable token
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRefreshNotRefreshable()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $jwt = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => false,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(405, $response->get_status());
        $this->assertEquals('JWT token is not refreshable', $response->get_data()['reason']);
    }

    /**
     * Verify that token is revoked properly
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRevoked()
    {
        $service = AAM_Service_Jwt::getInstance();

        // Issue a token that later we'll refresh
        $jwt = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID, null, null, true);

        $this->assertTrue($service->revokeUserToken(AAM_UNITTEST_ADMIN_USER_ID, $jwt));

        // Reset cache
        wp_cache_flush();

        $tokens = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(in_array($jwt, $tokens, true));
    }

    /**
     * Verify that token can be refreshed successfully
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTokenRevokeValid()
    {
        $server  = rest_get_server();
        $service = AAM_Service_Jwt::getInstance();

        // Issue a token that later we'll refresh
        $jwt = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID, null, null, true);

        // Refresh token
        $request = new WP_REST_Request('POST', '/aam/v2/jwt/revoke');
        $request->set_param('jwt', $jwt);

        $response = $server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

}