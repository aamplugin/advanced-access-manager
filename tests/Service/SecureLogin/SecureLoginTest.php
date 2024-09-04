<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\SecureLogin;

use WP_Session_Tokens,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM_Framework_Utility_Cache,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Secure login features
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class SecureLoginTest extends TestCase
{
    use ResetTrait;

    /**
     * Test that "One Session Per User" works as expected
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testOneSessionPerUser()
    {
        // Enable "One Session Per User" feature
        AAM_Framework_Manager::configs()->set_config(
            'service.secureLogin.feature.singleSession', true
        );

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Define valid credentials
        $creds = array(
            'user_login'    => AAM_UNITTEST_USERNAME,
            'user_password' => AAM_UNITTEST_PASSWORD
        );

        // Sign-in user first time
        $user = wp_signon($creds);
        $this->assertEquals('WP_User', get_class($user));

        // Now try to authenticate user again
        $user = wp_signon($creds);
        $this->assertEquals('WP_User', get_class($user));

        // Finally verify that there is only one session persisted
        $sessions = WP_Session_Tokens::get_instance($user->ID);
        $this->assertCount(1, $sessions->get_all());

        // Reset all sessions
        $sessions->destroy_all();
    }

    /**
     * Test the "Brute Force Lockout" feature
     *
     * Authentication process has to return WP_Error if number of allowed attempts
     * exceeded its limit
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testBruteForceLockout()
    {
        // Enable "Brute Force Lockout" feature
        AAM_Framework_Manager::configs()->set_config(
            'service.secureLogin.feature.bruteForceLockout', true
        );

        // Force dummy user IP
        $ip                     = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $ip;

        // Force to max out the number of attempts
        AAM_Framework_Utility_Cache::set('failed_login_attempts_' . $ip, 50, time() + 10);

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Define valid credentials
        $creds = array(
            'user_login'    => AAM_UNITTEST_USERNAME,
            'user_password' => AAM_UNITTEST_PASSWORD
        );

        // Sign-in user first time
        $user = wp_signon($creds);

        $this->assertEquals('WP_Error', get_class($user));
        $this->assertEquals('Exceeded maximum number for authentication attempts. Try again later.', $user->get_error_message());

        // Also make sure that attempts counter was increased
        $this->assertEquals(51, AAM_Framework_Utility_Cache::get('failed_login_attempts_' . $ip));

        // Reset original state
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test that it fails to authenticate locked user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUserLockedStatus()
    {
        add_user_meta(AAM_UNITTEST_ADMIN_USER_ID, 'aam_user_status', 'locked');

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Define valid credentials
        $creds = array(
            'user_login'    => AAM_UNITTEST_USERNAME,
            'user_password' => AAM_UNITTEST_PASSWORD
        );

        // Sign-in user first time
        $user = wp_signon($creds);
        $this->assertEquals('WP_Error', get_class($user));
        $this->assertEquals('[ERROR]: User is inactive. Contact website administrator.', $user->get_error_message());

        // Restore user status
        delete_user_meta(AAM_UNITTEST_ADMIN_USER_ID, 'aam_user_status');
    }

}