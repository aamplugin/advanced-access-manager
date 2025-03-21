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
    AAM_Core_Jwt_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Jwt service tests
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class JwtRestApiTest extends TestCase
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
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => false,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $result->token);

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
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $result->token);

        $response = $server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
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
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => false,
            'exp'         => DateTime::createFromFormat('m/d/Y', '01/01/2018')->getTimestamp()
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/validate');
        $request->set_param('jwt', $result->token);

        $response = $server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('rest_jwt_validation_failure', $response->get_data()['code']);
        $this->assertEquals('Expired token', $response->get_data()['reason']);
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
        $request->set_param('jwt', $jwt->token);

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
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => true,
            'exp'         => DateTime::createFromFormat('m/d/Y', '01/01/2018')->getTimestamp()
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', $result->token);

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
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => false,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/aam/v2/jwt/refresh');
        $request->set_param('jwt', $result->token);

        $response = $server->dispatch($request);

        $this->assertEquals(405, $response->get_status());
        $this->assertEquals('JWT token is not refreshable', $response->get_data()['reason']);
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
        $request->set_param('jwt', $jwt->token);

        $response = $server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Verify that issued JWT is no longer valid if it is not part of JWT registry
     *
     * @access public
     * @version 6.9.4
     */
    public function testRevokedValidToken()
    {
        $server = rest_get_server();

        // Generate valid JWT token
        $result = AAM_Core_Jwt_Manager::getInstance()->encode(array(
            'userId'      => AAM_UNITTEST_ADMIN_USER_ID,
            'revocable'   => true,
            'refreshable' => false
        ));

        $request = new WP_REST_Request('POST', '/wp/v2/posts');
        $request->set_header('Authorization', "Bearer {$result->token}");
        $request->set_body(json_encode(array(
            'title' => 'Test'
        )));

        $response = $server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        $this->assertEquals('Sorry, you are not allowed to create posts as this user.', $response->get_data()['message']);
    }

    /**
     * Verify that issued JWT is is actually valid for the same operation as tested
     * in the `testRevokedValidToken` test case.
     *
     * @access public
     * @version 6.9.4
     * @todo - Figure out how to properly test RESTful API calls
     */
    // public function testValidToken()
    // {
    //     $server = rest_get_server();

    //     // Generate valid JWT token
    //     $service = AAM_Service_Jwt::getInstance();

    //     // Issue a token that later we'll refresh
    //     $jwt = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID, null, null, true);

    //     // Reset cache
    //     wp_cache_flush();

    //     $request = new WP_REST_Request('POST', '/wp/v2/posts');
    //     $_GET['aam-jwt'] = "Bearer {$jwt}";
    //     // $request->set_header('Authorization', "Bearer {$jwt}");
    //     $request->set_body(json_encode(array(
    //         'title' => 'Test'
    //     )));

    //     $response = $server->dispatch($request);

    //     $this->assertEquals(201, $response->get_status());

    //     unset($_SERVER['aam-jwt']);
    // }

}