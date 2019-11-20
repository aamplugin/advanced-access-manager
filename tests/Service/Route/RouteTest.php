<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Route;

use AAM,
    WP_REST_Request,
    AAM_Core_Config,
    AAM_Service_Route,
    AAM_Core_Object_Route,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * API Routes service tests
 *
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class RouteTest extends TestCase
{
    use ResetTrait;

    /**
     * Test that XML-PRC is disabled
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDisabledXMLRPC()
    {
        AAM_Core_Config::set('core.settings.xmlrpc', false);

        $this->assertFalse(apply_filters('xmlrpc_enabled', true));
    }

    /**
     * Test that RESTful API is disabled
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDisabledRESTfulAPI()
    {
        AAM_Core_Config::set('core.settings.restful', false);

        $error = apply_filters('rest_authentication_errors', null);

        $this->assertEquals('WP_Error', get_class($error));
        $this->assertEquals('RESTful API is disabled', $error->get_error_message());
    }

    /**
     * Assert that jwt token is generated for the authentication request
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testRestrictedRESTfulEndpoint()
    {
        global $wp;

        $object = AAM::getUser()->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

        // Restrict AAM authentication endpoint
        $this->assertTrue(
            $object->updateOptionItem('restful|/aam/v2/authenticate|post', true)->save()
        );

        // Register all the necessary hooks

        $wp->query_vars['rest_route'] = true;
        AAM_Service_Route::getInstance()->registerRouteControllers();

        $server = rest_get_server();

        $request = new WP_REST_Request('POST', '/aam/v2/authenticate');
        $request->set_param('username', AAM_UNITTEST_USERNAME);
        $request->set_param('password', AAM_UNITTEST_PASSWORD);

        $error = $server->dispatch($request);

        $this->assertEquals('WP_Error', get_class($error));
        $this->assertEquals('Access Denied', $error->get_error_message());
    }

}