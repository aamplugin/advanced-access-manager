<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Uri;

use AAM,
    AAM_Service_Uri,
    AAM_Core_Object_Uri,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * URI Access service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class UriTest extends TestCase
{
    use ResetTrait;

    /**
     * Test default "Access Denied" message
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAccessDeniedMessage()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'default',
            'action' => null
        ))->save();

        $this->assertTrue($result);

        // Override the default handlers so we can suppress die exit
        add_filter('wp_die_handler', function() {
            return function($message, $title) {
                _default_wp_die_handler($message, $title, array('exit' => false));
            };
        }, PHP_INT_MAX);
        $_SERVER['REQUEST_URI'] = '/hello-world';

        ob_start();
        AAM_Service_Uri::getInstance()->authorizeUri();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Access Denied', $content);
    }

    /**
     * Test custom wp_die message
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCustomMessage()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'message',
            'action' => 'This is not allowed'
        ))->save();

        $this->assertTrue($result);

        // Override the default handlers so we can suppress die exit
        add_filter('wp_die_handler', function() {
            return function($message, $title) {
                _default_wp_die_handler($message, $title, array('exit' => false));
            };
        }, PHP_INT_MAX);
        $_SERVER['REQUEST_URI'] = '/hello-world';

        ob_start();
        AAM_Service_Uri::getInstance()->authorizeUri();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('This is not allowed', $content);
    }

    /**
     * Test redirect to the custom page
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectToExistingPage()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'page',
            'action' => AAM_UNITTEST_PAGE_ID
        ))->save();

        $this->assertTrue($result);

        $_SERVER['REQUEST_URI'] = '/hello-world';

        AAM_Service_Uri::getInstance()->authorizeUri();

        $this->assertContains(
            'Location: ' . get_page_link(AAM_UNITTEST_PAGE_ID), xdebug_get_headers()
        );
    }

    /**
     * Test redirect to the local URL
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRedirectToUrl()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'url',
            'action' => '/another-page'
        ))->save();

        $this->assertTrue($result);

        $_SERVER['REQUEST_URI'] = '/hello-world';

        AAM_Service_Uri::getInstance()->authorizeUri();

        $this->assertContains(
            'Location: /another-page', xdebug_get_headers()
        );
    }

    /**
     * Test trigger of the callback function
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testTriggerCallback()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'callback',
            'action' => 'AAM\\UnitTest\\Service\\Uri\\Callback::redirectCallback'
        ))->save();

        $this->assertTrue($result);

        $_SERVER['REQUEST_URI'] = '/hello-world';

        AAM_Service_Uri::getInstance()->authorizeUri();

        $this->assertContains(
            'Location: ' . Callback::REDIRECT_URL, xdebug_get_headers()
        );
    }

}