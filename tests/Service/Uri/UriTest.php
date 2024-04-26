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
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\HeaderTrait;

/**
 * URI Access service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class UriTest extends TestCase
{
    use ResetTrait,
        HeaderTrait;

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

        $_SERVER['REQUEST_URI'] = '/hello-world';

        ob_start();
        AAM_Service_Uri::getInstance()->authorizeUri();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Access Denied', $content);
    }

    /**
     * Test case-insensitive rule
     *
     * @return void
     *
     * @link https://github.com/aamplugin/advanced-access-manager/issues/105
     * @access public
     * @version 6.5.0
     */
    public function testCaseInsensitive()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $result = $object->updateOptionItem('/hello-world', array(
            'type'   => 'default',
            'action' => null
        ))->save();

        $this->assertTrue($result);

        $_SERVER['REQUEST_URI'] = '/Hello-world';

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
            'action' => self::$page_id
        ))->save();

        $this->assertTrue($result);

        $_SERVER['REQUEST_URI'] = '/hello-world';

        AAM_Service_Uri::getInstance()->authorizeUri();

        $this->assertContains(
            'Location: ' . get_page_link(self::$page_id), $this->getAllHeaders()
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
            'Location: /another-page', $this->getAllHeaders()
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
            'Location: ' . Callback::REDIRECT_URL, $this->getAllHeaders()
        );
    }

}