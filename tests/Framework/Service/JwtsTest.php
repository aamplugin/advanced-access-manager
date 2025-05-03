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
    AAM_Framework_Service_Jwts,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "JWTs" framework service
 */
final class JwtsTest extends TestCase
{

    /**
     * Test that we can issue a token
     *
     * @return void
     */
    public function testIssueToken()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a);

        // Issue token and ensure that it has all the necessary attributed properly
        // defined
        $token = $service->issue([ 'test' => 'yes' ], [
            'ttl'       => 1000,
            'revocable' => false
        ]);

        $this->assertIsArray($token);
        $this->assertArrayHasKey('token', $token);
        $this->assertArrayHasKey('claims', $token);

        $this->assertArrayHasKey('iat', $token['claims']);
        $this->assertArrayHasKey('iss', $token['claims']);
        $this->assertArrayHasKey('exp', $token['claims']);
        $this->assertArrayHasKey('jti', $token['claims']);
        $this->assertArrayHasKey('user_id', $token['claims']);
        $this->assertArrayHasKey('test', $token['claims']);
        $this->assertArrayHasKey('revocable', $token['claims']);
        $this->assertEquals($token['claims']['user_id'], $user_a);
        $this->assertEquals($token['claims']['test'], 'yes');
        $this->assertEquals($token['claims']['iss'], get_site_url());
        $this->assertFalse($token['claims']['revocable']);
        $this->assertTrue($token['claims']['exp'] >= time() + 998);
    }

    /**
     * Test that we can issue revocable token and it is stored correctly in the
     * registry
     *
     * @return void
     */
    public function testIssueRevocableToken()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a);

        // Issue token with default attributes
        $token = $service->issue();

        $this->assertIsArray($token);

        // Verify all the required attributes
        $this->assertArrayHasKey('iat', $token['claims']);
        $this->assertArrayHasKey('iss', $token['claims']);
        $this->assertArrayHasKey('exp', $token['claims']);
        $this->assertArrayHasKey('jti', $token['claims']);
        $this->assertArrayHasKey('user_id', $token['claims']);
        $this->assertArrayHasKey('revocable', $token['claims']);
        $this->assertEquals($token['claims']['user_id'], $user_a);
        $this->assertEquals($token['claims']['iss'], get_site_url());
        $this->assertTrue($token['claims']['revocable']);
        $this->assertTrue($token['claims']['exp'] >= time() + 86398);

        // Read user's option and verify that token is stored there
        $registry = get_user_option(AAM_Framework_Service_Jwts::DB_OPTION, $user_a);

        $this->assertIsArray($registry);
        $this->assertContains($token['token'], $registry);
    }

    /**
     * Test that we can issue & revoke token successfully
     *
     * @return void
     */
    public function testRevokeToken()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a);

        // Issue token with default attributes
        $token = $service->issue();

        $this->assertIsArray($token);

        // Revoke the token and verify that the registry is empty
        $this->assertTrue($service->revoke($token['token']));

        // Read user's option and verify that token is stored there
        $registry = get_user_option(AAM_Framework_Service_Jwts::DB_OPTION, $user_a);

        $this->assertIsArray($registry);
        $this->assertEmpty($registry);
    }

    /**
     * Test that the size of the registry is properly handled when number of issued
     * token exceed the maximum allowed number
     *
     * @return void
     */
    public function testTokenRegistryOverflow()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a);

        // Set the limit
        $this->assertTrue(AAM::api()->config->set('service.jwt.registry_size', 2));

        // Issue 2 tokens first and fill in the registry, then issue 1 that exceed the
        // limit
        $token_a = $service->issue([ 'token' => 'a' ]);
        $token_b = $service->issue([ 'token' => 'b' ]);
        $token_c = $service->issue([ 'token' => 'c' ]);

        // Read user's option and verify that token is stored there
        $registry = get_user_option(AAM_Framework_Service_Jwts::DB_OPTION, $user_a);

        $this->assertNotContains($token_a['token'], $registry);
        $this->assertContains($token_b['token'], $registry);
        $this->assertContains($token_c['token'], $registry);
    }

    /**
     * Test that token validation method works
     *
     * Note! This is a limited validation and tests only if token exists
     *
     * @return void
     */
    public function testValidateToken()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a, [
            'error_handling' => 'wp_error'
        ]);

        // Set the limit
        $this->assertTrue(AAM::api()->config->set('service.jwt.registry_size', 1));

        // Issue 2 tokens first and fill in the registry, then issue 1 that exceed the
        // limit
        $token_a = $service->issue([ 'token' => 'a' ]);
        $token_b = $service->issue([ 'token' => 'b' ]);

        $result_a = $service->validate($token_a['token']);
        $result_b = $service->validate($token_b['token']);

        $this->assertEquals(\WP_Error::class, get_class($result_a));
        $this->assertEquals('Unregistered token', $result_a->get_error_message());

        $this->assertTrue($result_b);
    }

    /**
     * Test the refresh token method
     *
     * @return void
     */
    public function testTokenRefresh()
    {
        $user_a  = $this->createUser([ 'role' => 'subscriber' ]);
        $service = AAM::api()->jwts('user:' . $user_a);

        // Issue a refreshable token first
        $token_a = $service->issue([ 'test' => 't' ], [
            'ttl'         => 100,
            'revocable'   => true,
            'refreshable' => true
        ]);

        $this->assertIsArray($token_a);

        // Refresh the token
        $token_b = $service->refresh($token_a['token']);

        $this->assertIsArray($token_b);

        // Make sure that ttl is the same
        $ttl = $token_b['claims']['exp'] - $token_b['claims']['iat'];

        $this->assertLessThanOrEqual(100, $ttl);

        // The custom claim is still part of a new token
        $this->assertArrayHasKey('test', $token_b['claims']);
        $this->assertEquals('t', $token_b['claims']['test']);

        // The refreshed token is no longer part of the registry
        // Read user's option and verify that token is stored there
        $registry = get_user_option(AAM_Framework_Service_Jwts::DB_OPTION, $user_a);

        $this->assertNotContains($token_a['token'], $registry);
        $this->assertContains($token_b['token'], $registry);
    }

}