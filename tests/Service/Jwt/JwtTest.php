<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Jwt;

use AAM_Service_Jwt,
    AAM_Core_Jwt_Manager,
    AAM_Framework_Manager,
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
     * Testing the standard header bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerStandardHeader()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'header');

        // Emulate token set
        $_SERVER['HTTP_AUTHORIZATION'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * Testing the custom header bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerCustomHeader()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'header');
        AAM_Framework_Manager::configs()->set_config('service.jwt.header_name', 'X-JWT');

        // Emulate token set
        $_SERVER['X-JWT'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_SERVER['X-JWT']);
    }

    /**
     * Testing the standard query param bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerStandardQueryParam()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'query_param');

        // Emulate token set
        $_GET['aam-jwt'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_GET['aam-jwt']);
    }

    /**
     * Testing the custom query param bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerCustomQueryParam()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'query_param');
        AAM_Framework_Manager::configs()->set_config('service.jwt.query_param_name', 'jwt');

        // Emulate token set
        $_GET['jwt'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_GET['jwt']);
    }

    /**
     * Testing the standard post param bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerStandardPostParam()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'post_param');

        // Emulate token set
        $_POST['aam-jwt'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_POST['aam-jwt']);
    }

    /**
     * Testing the custom post param bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerCustomPostParam()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'post_param');
        AAM_Framework_Manager::configs()->set_config('service.jwt.post_param_name', 'jwt');

        // Emulate token set
        $_POST['jwt'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_POST['jwt']);
    }

    /**
     * Testing the cookie bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerStandardCookie()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only header
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'cookie');

        // Emulate token set
        $_COOKIE['aam_jwt_token'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_COOKIE['aam_jwt_token']);
    }

    /**
     * Testing the custom cookie bearer
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerCustomCookie()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only cookie
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'cookie');
        AAM_Framework_Manager::configs()->set_config('service.jwt.cookie_name', 'jwt_cookie');

        // Emulate token set
        $_COOKIE['jwt_cookie'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_COOKIE['jwt_cookie']);
    }

    /**
     * Testing the bearer waterfall
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenBearerWaterfall()
    {
        // Let's issue the token first
        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        // Only cookie
        AAM_Framework_Manager::configs()->set_config('service.jwt.bearer', 'header,cookie');
        AAM_Framework_Manager::configs()->set_config('service.jwt.cookie_name', 'jwt_test_cookie');

        // Emulate token set
        $_COOKIE['jwt_test_cookie'] = $issued->token;

        $this->assertEquals(
            AAM_UNITTEST_ADMIN_USER_ID,
            apply_filters('determine_current_user', null)
        );

        // Reset global vars
        unset($_COOKIE['jwt_test_cookie']);
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
        $this->assertTrue(in_array($res->token, $tokens, true));
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
        AAM_Framework_Manager::configs()->set_config('service.jwt.registry_size', 1);

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

        $this->assertFalse(in_array($res1->token, $tokens, true));
        $this->assertTrue(in_array($res2->token, $tokens, true));
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

        $this->assertTrue(
            $service->revokeUserToken(AAM_UNITTEST_ADMIN_USER_ID, $jwt->token)
        );

        // Reset cache
        wp_cache_flush();

        $tokens = $service->getTokenRegistry(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertFalse(in_array($jwt->token, $tokens, true));
    }

    /**
     * Test token expiration setting as a string
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenCustomExpirationString()
    {
        $service = AAM_Service_Jwt::getInstance();

        // Set custom expiration as string
        AAM_Framework_Manager::configs()->set_config('service.jwt.expires_in', '1 hour');

        $issued = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertEquals($issued->claims['exp'], time() + 3600);
    }

    /**
     * Test token expiration setting as a number
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenCustomExpirationNumber()
    {
        $service = AAM_Service_Jwt::getInstance();

        // Set custom expiration as string
        AAM_Framework_Manager::configs()->set_config('service.jwt.expires_in', 20);

        $issued = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        $this->assertEquals($issued->claims['exp'], time() + 20);
    }

    /**
     * Test custom signing algorithm
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenCustomSigningAlg()
    {
        AAM_Framework_Manager::configs()->set_config('service.jwt.signing_algorithm', 'HS512');

        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);

        $headers = AAM_Core_Jwt_Manager::getInstance()->extractHeaders(
            $issued->token
        );

        $this->assertEquals($headers->alg, 'HS512');
    }

    /**
     * Test custom signing secret
     *
     * @return void
     * @version 6.9.11
     */
    public function testTokenCustomSigningSecret()
    {
        AAM_Framework_Manager::configs()->set_config('service.jwt.signing_secret', '123');

        $service = AAM_Service_Jwt::getInstance();
        $issued  = $service->issueToken(AAM_UNITTEST_ADMIN_USER_ID);
        $result  = AAM_Core_Jwt_Manager::getInstance()->validate($issued->token);

        $this->assertFalse(is_wp_error($result));
    }

}