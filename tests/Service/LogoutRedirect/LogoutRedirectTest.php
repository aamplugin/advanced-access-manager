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
    AAM_Core_Object_LogoutRedirect,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Logout Redirect feature
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class LogoutRedirectTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
        $object->updateOptionItem('logout.redirect.type', 'page')
            ->updateOptionItem('logout.redirect.page', AAM_UNITTEST_PAGE_ID)
            ->save();

        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains('Location: ' . get_page_link(AAM_UNITTEST_PAGE_ID), xdebug_get_headers());
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
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
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
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
        $object->updateOptionItem('logout.redirect.type', 'callback')
            ->updateOptionItem('logout.redirect.callback', 'AAM\\UnitTest\\Service\\LogoutRedirect\\Callback::redirectCallback')
            ->save();

        do_action('clear_auth_cookie');
        do_action('wp_logout');

        $this->assertContains('Location: ' . Callback::REDIRECT_URL, xdebug_get_headers());
    }
}
