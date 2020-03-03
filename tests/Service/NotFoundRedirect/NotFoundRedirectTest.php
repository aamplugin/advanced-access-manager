<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\NotFoundRedirect;

use AAM_Core_Config,
    PHPUnit\Framework\TestCase,
    AAM_Service_NotFoundRedirect,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * 404 Redirect service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class NotFoundRedirectTest extends TestCase
{
    use ResetTrait;

    /**
     * Test the default 404 redirect
     *
     * AAM should not issue any redirect headers
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDefault404Redirect()
    {
        global $wp_query;

        // Force 404 path
        $wp_query->is_404 = true;
        $service = AAM_Service_NotFoundRedirect::getInstance();

        // Reset any already sent "Location" headers. This way insure that no other
        // redirect headers are sent
        header('Location: empty');

        $service->wp();

        $this->assertContains('Location: empty', xdebug_get_headers());

        // Reset to default
        $wp_query->is_404 = null;
    }

    /**
     * Test redirect to the existing page
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testExistingPageLogoutRedirect()
    {
        global $wp_query;

        // Set 404 config
        $object = \AAM::getUser()->getObject(
            \AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
        );
        $object->store('404.redirect.type', 'page');
        $object->store('404.redirect.page', AAM_UNITTEST_PAGE_ID);

        // Reset cache
        $this->_resetSubjects();

        // Force 404 path
        $wp_query->is_404 = true;
        $service = AAM_Service_NotFoundRedirect::getInstance();

        $service->wp();

        $this->assertContains(
            'Location: ' . get_page_link(AAM_UNITTEST_PAGE_ID), xdebug_get_headers()
        );

        // Reset to default
        $wp_query->is_404 = null;
    }

    /**
     * Test redirect to the defined URL
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testUrlLogoutRedirect()
    {
        global $wp_query;

        // Set 404 config
        $object = \AAM::getUser()->getObject(
            \AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
        );
        $object->store('404.redirect.type', 'url');
        $object->store('404.redirect.url', '/hello-world');

        // Reset cache
        $this->_resetSubjects();

        // Force 404 path
        $wp_query->is_404 = true;
        $service = AAM_Service_NotFoundRedirect::getInstance();

        $service->wp();

        $this->assertContains('Location: /hello-world', xdebug_get_headers());

        // Reset to default
        $wp_query->is_404 = null;
    }

    /**
     * Test execution of the callback function as redirect
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testCallbackLogoutRedirect()
    {
        global $wp_query;

        // Set 404 config
        $object = \AAM::getUser()->getObject(
            \AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
        );
        $object->store('404.redirect.type', 'callback');
        $object->store('404.redirect.callback', 'AAM\\UnitTest\\Service\\NotFoundRedirect\\Callback::redirectCallback');

        // Reset cache
        $this->_resetSubjects();

        // Force 404 path
        $wp_query->is_404 = true;
        $service = AAM_Service_NotFoundRedirect::getInstance();

        $service->wp();

        $this->assertContains('Location: ' . Callback::REDIRECT_URL, xdebug_get_headers());

        // Reset to default
        $wp_query->is_404 = null;
    }

}