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
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Access Denied Redirect service
 * 
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class DeniedRedirectTest extends TestCase
{
    use ResetTrait;

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
        $redirect->updateOptionItem('frontend.redirect.page', AAM_UNITTEST_PAGE_ID);
        
        $this->assertTrue($redirect->save());

        // Reset all internal cache
        $this->_resetSubjects();

        // Capture the WP Die message
        ob_start();
        wp_die('Access Denied', 'aam_access_denied', array('exit' => false));
        ob_end_clean();

        $this->assertContains('Location: ' . get_page_link(AAM_UNITTEST_PAGE_ID), xdebug_get_headers());
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

        $this->assertContains('Location: /hello-world', xdebug_get_headers());
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

        $this->assertContains('Location: ' . add_query_arg(
            array('reason' => 'restricted'), wp_login_url()
        ), xdebug_get_headers());
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
        $redirect->updateOptionItem('frontend.redirect.callback', 'AAM\\UnitTest\\Service\\DeniedRedirect\\Callback::printOutput');
        
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