<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\NotFoundRedirect;

use PHPUnit\Framework\TestCase,
    AAM_Service_NotFoundRedirect,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\HeaderTrait;

/**
 * 404 Redirect service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class NotFoundRedirectTest extends TestCase
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
        $this->setHeader('Location: empty');

        $service->wp();

        $this->assertContains('Location: empty', $this->getAllHeaders());

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
        $object->store('404.redirect.page', self::$page_id);

        // Reset cache
        $this->_resetSubjects();

        // Force 404 path
        $wp_query->is_404 = true;
        $service = AAM_Service_NotFoundRedirect::getInstance();

        $service->wp();

        $this->assertContains(
            'Location: ' . get_page_link(self::$page_id),
            $this->getAllHeaders()
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

        $this->assertContains('Location: /hello-world', $this->getAllHeaders());

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

        $this->assertContains(
            'Location: ' . Callback::REDIRECT_URL,
            $this->getAllHeaders()
        );

        // Reset to default
        $wp_query->is_404 = null;
    }

}