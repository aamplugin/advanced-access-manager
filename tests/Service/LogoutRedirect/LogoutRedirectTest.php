<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\LogoutRedirect;

use AAM,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Core_Object_LogoutRedirect;

/**
 * Logout Redirect feature
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class LogoutRedirectTest extends TestCase
{
    use ResetTrait;

    protected static $post_id;

    /**
     * Targeting page ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $page_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        // Create dummy post to avoid problem with getCurrentPost
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Sample Post',
            'post_status' => 'publish'
        ));

         // Setup a default page
         self::$page_id = wp_insert_post(array(
            'post_title'  => 'Login Redirect Service Page',
            'post_name'   => 'login-redirect-service-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));

    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

    /**
     * Test the default logout redirect
     *
     * AAM should not issue any redirect headers
     *
     * @return void
     *
     * @since 6.0.5 Split logout process between `clear_auth_cookie` and `wp_logout`
     * @since 6.0.0 Initial implementation of the test case
     *
     * @access public
     * @version 6.0.5
     */
    public function testDefaultLogoutRedirect()
    {
        // Reset any already sent "Location" headers. This way insure that no other
        // redirect headers are sent
        header('Location: empty');
        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains('Location: empty', xdebug_get_headers());
    }

    /**
     * Test redirect to the existing page
     *
     * @return void
     *
     * @since 6.0.5 Split logout process between `clear_auth_cookie` and `wp_logout`
     * @since 6.0.0 Initial implementation of the test case
     *
     * @access public
     * @version 6.0.5
     */
    public function testExistingPageLogoutRedirect()
    {
        $object  = AAM::getUser()->getObject(
            AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true
        );

        $object->updateOptionItem('logout.redirect.type', 'page')
            ->updateOptionItem('logout.redirect.page', self::$page_id)
            ->save();

        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains(
            'Location: ' . get_page_link(self::$page_id), xdebug_get_headers()
        );
    }

    /**
     * Test redirect to the defined URL
     *
     * @return void
     *
     * @since 6.0.5 Split logout process between `clear_auth_cookie` and `wp_logout`
     * @since 6.0.0 Initial implementation of the test case
     *
     * @access public
     * @version 6.0.5
     */
    public function testUrlLogoutRedirect()
    {
        $object  = AAM::getUser()->getObject(
            AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true
        );

        $object->updateOptionItem('logout.redirect.type', 'url')
            ->updateOptionItem('logout.redirect.url', '/hello-world')
            ->save();

        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains('Location: /hello-world', xdebug_get_headers());
    }

    /**
     * Test execution of the callback function as redirect
     *
     * @return void
     *
     * @since 6.0.5 Split logout process between `clear_auth_cookie` and `wp_logout`
     * @since 6.0.0 Initial implementation of the test case
     *
     * @access public
     * @version 6.0.5
     */
    public function testCallbackLogoutRedirect()
    {
        $object  = AAM::getUser()->getObject(
            AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true
        );

        $object->updateOptionItem('logout.redirect.type', 'callback')
            ->updateOptionItem('logout.redirect.callback', 'AAM\\UnitTest\\Service\\LogoutRedirect\\Callback::redirectCallback')
            ->save();

        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains('Location: ' . Callback::REDIRECT_URL, xdebug_get_headers());
    }

}