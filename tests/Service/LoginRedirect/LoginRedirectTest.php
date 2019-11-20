<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\LoginRedirect;

use WP_REST_Request,
    AAM_Core_Subject_User,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Core_Object_LoginRedirect;

/**
 * Login Redirect feature
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class LoginRedirectTest extends TestCase
{
    use ResetTrait;

    /**
     * Assert that correct URL login redirect is returns for RESTful auth call
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRESTfulLoginURLRedirect()
    {
        $server = rest_get_server();

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Set login redirect
        $subject = new AAM_Core_Subject_User(AAM_UNITTEST_JOHN_ID);
        $object  = $subject->getObject(AAM_Core_Object_LoginRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'login.redirect.type' => 'url',
            'login.redirect.url'  => 'https://aamplugin.com'
        ));
        $object->save();

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('WP_User', get_class($data['user']));
        $this->assertEquals('https://aamplugin.com', $data['redirect']);
    }

    /**
     * Assert that correct Page login redirect is returns for RESTful auth call
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRESTfulLoginPageRedirect()
    {
        $server = rest_get_server();

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Set login redirect
        $subject = new AAM_Core_Subject_User(AAM_UNITTEST_JOHN_ID);
        $object  = $subject->getObject(AAM_Core_Object_LoginRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'login.redirect.type' => 'page',
            'login.redirect.page' => AAM_UNITTEST_PAGE_ID
        ));
        $object->save();

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('WP_User', get_class($data['user']));
        $this->assertEquals(get_page_link(AAM_UNITTEST_PAGE_ID), $data['redirect']);
    }

    /**
     * Assert that correct login redirect is returns for RESTful auth call for
     * callback type of redirect
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRESTfulLoginCallbackRedirect()
    {
        $server = rest_get_server();

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        // Set login redirect
        $subject = new AAM_Core_Subject_User(AAM_UNITTEST_JOHN_ID);
        $object  = $subject->getObject(AAM_Core_Object_LoginRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'login.redirect.type'     => 'callback',
            'login.redirect.callback' => 'AAM\\UnitTest\\Service\\LoginRedirect\\Callback::redirectCallback'
        ));
        $object->save();

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('WP_User', get_class($data['user']));
        $this->assertEquals(Callback::REDIRECT_URL, $data['redirect']);
    }

    /**
     * Assert that null login redirect is returns for RESTful auth call
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRESTfulLoginDefaultRedirect()
    {
        $server = rest_get_server();

        // No need to generate Auth cookies
        add_filter('send_auth_cookies', '__return_false');

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals('WP_User', get_class($data['user']));
        $this->assertNull($data['redirect']);
    }

    /**
     * Validate that `login_redirect` filter is triggered with AAM hook
     *
     * Make sure that user will be redirected to the existing page
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLoginRedirectHookTriggerChanges()
    {
        // Set login redirect
        $subject = new AAM_Core_Subject_User(AAM_UNITTEST_JOHN_ID);
        $object  = $subject->getObject(AAM_Core_Object_LoginRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'login.redirect.type' => 'page',
            'login.redirect.page' => AAM_UNITTEST_PAGE_ID
        ));
        $object->save();

        $redirect = apply_filters('login_redirect', admin_url(), admin_url(), $subject->getPrincipal());

        $this->assertEquals(get_page_link(AAM_UNITTEST_PAGE_ID), $redirect);
    }

    /**
     * Validate that `login_redirect` filter is triggered with AAM hook
     *
     * Make sure that user will be redirected to originally defined destination. By
     * default AAM overwrites only destinations that are different than admin_url()
     * return.
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLoginRedirectHookTriggerPersistOriginalRedirect()
    {
        // Set login redirect
        $subject = new AAM_Core_Subject_User(AAM_UNITTEST_JOHN_ID);
        $object  = $subject->getObject(AAM_Core_Object_LoginRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'login.redirect.type' => 'url',
            'login.redirect.url'  => 'https://aamplugin.com'
        ));
        $object->save();

        $redirect = apply_filters(
            'login_redirect',
            get_page_link(AAM_UNITTEST_PAGE_ID),
            get_page_link(AAM_UNITTEST_PAGE_ID),
            $subject->getPrincipal()
        );

        $this->assertEquals(get_page_link(AAM_UNITTEST_PAGE_ID), $redirect);
    }

}