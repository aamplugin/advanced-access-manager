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
     * @access public
     * @version 6.0.0
     */
    public function testDefaultLogoutRedirect()
    {
        // Reset any already sent "Location" headers. This way insure that no other
        // redirect headers are sent
        header('Location: empty');
        do_action('wp_logout');

        $this->assertContains('Location: empty', xdebug_get_headers());
    }

    /**
     * Test redirect to the existing page
     *
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testExistingPageLogoutRedirect()
    { 
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'logout.redirect.type' => 'page',
            'logout.redirect.page' => AAM_UNITTEST_PAGE_ID
        ));
        $object->save();

        do_action('wp_logout');

        $this->assertContains('Location: ' . get_page_link(AAM_UNITTEST_PAGE_ID), xdebug_get_headers());
    }

    /**
     * Test redirect to the defined URL
     * 
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testUrlLogoutRedirect()
    { 
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'logout.redirect.type' => 'url',
            'logout.redirect.url' => '/hello-world'
        ));
        $object->save();

        do_action('wp_logout');

        $this->assertContains('Location: /hello-world', xdebug_get_headers());
    }

    /**
     * Test execution of the callback function as redirect
     *
     * @return void
     * 
     * @access public
     * @version 6.0.0
     */
    public function testCallbackLogoutRedirect()
    { 
        $object  = AAM::getUser()->getObject(AAM_Core_Object_LogoutRedirect::OBJECT_TYPE, null, true);
        $object->setOption(array(
            'logout.redirect.type' => 'callback',
            'logout.redirect.callback' => 'AAM\\UnitTest\\Service\\LogoutRedirect\\Callback::redirectCallback'
        ));
        $object->save();

        do_action('wp_logout');

        $this->assertContains('Location: ' . Callback::REDIRECT_URL, xdebug_get_headers());
    }

}