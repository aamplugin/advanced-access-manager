<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\DeniedRedirect;

use AAM,
    AAM_Core_Object_Redirect,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\HeaderTrait;

/**
 * Access Denied Redirect service
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class DeniedRedirectTest extends TestCase
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
            'post_title'  => 'Content Service Page',
            'post_name'   => 'content-service-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));
    }

    /**
     * Test default redirect which is "Access Denied" message
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDefaultRedirect()
    {
        // Capture the WP Die message
        ob_start();
        wp_die('Restricted Access', 'aam_access_denied', array('exit' => false));
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Access Denied', $content);
    }

    /**
     * Test custom WP Die message content
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCustomMessageRedirect()
    {
        // Define custom access denied message
        $redirect = AAM::getUser()->getObject(AAM_Core_Object_Redirect::OBJECT_TYPE);
        $redirect->updateOptionItem('frontend.redirect.type', 'message');
        $redirect->updateOptionItem('frontend.redirect.message', 'Denied by test');

        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Test', 'aam_access_denied', array('exit' => false));
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Denied by test', $content);
    }

    /**
     * Test redirect to the existing page
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testExistingPageRedirect()
    {
        // Define custom access denied message
        $redirect = AAM::getUser()->getObject(AAM_Core_Object_Redirect::OBJECT_TYPE);
        $redirect->updateOptionItem('frontend.redirect.type', 'page');
        $redirect->updateOptionItem('frontend.redirect.page', self::$page_id);

        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Access Denied', 'aam_access_denied', array('exit' => false));
        ob_end_clean();

        $this->assertContains(
            'Location: ' . get_page_link(self::$page_id),
            $this->getAllHeaders()
        );
    }

    /**
     * Test redirect to specified URI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUrlRedirect()
    {
        // Define custom access denied message
        $redirect = AAM::getUser()->getObject(AAM_Core_Object_Redirect::OBJECT_TYPE);
        $redirect->updateOptionItem('frontend.redirect.type', 'url');
        $redirect->updateOptionItem('frontend.redirect.url', '/hello-world');

        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Access Denied', 'aam_access_denied', array('exit' => false));
        ob_end_clean();

        $this->assertContains('Location: /hello-world', $this->getAllHeaders());
    }

    /**
     * Test redirect to the login screen
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLoginPageRedirect()
    {
        // Define custom access denied message
        $redirect = AAM::getUser()->getObject(AAM_Core_Object_Redirect::OBJECT_TYPE);
        $redirect->updateOptionItem('frontend.redirect.type', 'login');

        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Access Denied', 'aam_access_denied', array('exit' => false));
        ob_end_clean();

        $this->assertContains(
            'Location: ' . add_query_arg(array('reason' => 'restricted'), wp_login_url()),
            $this->getAllHeaders()
        );
    }

    /**
     * Test redirect to the PHP callback function
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCallbackRedirect()
    {
        // Define custom access denied message
        $redirect = AAM::getUser()->getObject(AAM_Core_Object_Redirect::OBJECT_TYPE);
        $redirect->updateOptionItem('frontend.redirect.type', 'callback');
        $redirect->updateOptionItem(
            'frontend.redirect.callback',
            'AAM\\UnitTest\\Service\\DeniedRedirect\\Callback::printOutput'
        );

        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Access Denied', 'aam_access_denied', array('exit' => false));
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString(Callback::OUTPUT, $content);
    }

}