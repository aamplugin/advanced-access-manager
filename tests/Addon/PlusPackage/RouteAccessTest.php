<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\PlusPackage;

use AAM,
    WP_REST_Request,
    AAM_Core_Object_Route,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test API Route access enhancement
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.4.0
 */
class RouteAccessTest extends TestCase
{
    use ResetTrait;

    /**
     * Test the wildcard for route
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testRouteWildCardMatch()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Route::OBJECT_TYPE);
        $this->assertTrue($object->updateOptionItem('restful|*|get', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $request  = new WP_REST_Request('GET', '/wp/v2/posts');
        $server   = rest_get_server();
        $response = $server->dispatch($request);

        $this->assertEquals('WP_REST_Response', get_class($response));
        $this->assertEquals('rest_access_denied', $response->data['code']);
        $this->assertEquals('Access Denied', $response->data['message']);
        $this->assertEquals(401, $response->data['data']['status']);
    }

    /**
     * Test the wildcard for method
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testMethodWildCardMatch()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Route::OBJECT_TYPE);
        $this->assertTrue($object->updateOptionItem('restful|/wp/v2/posts|*', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $request  = new WP_REST_Request('GET', '/wp/v2/posts');
        $server   = rest_get_server();
        $response = $server->dispatch($request);

        $this->assertEquals('WP_REST_Response', get_class($response));
        $this->assertEquals('rest_access_denied', $response->data['code']);
        $this->assertEquals('Access Denied', $response->data['message']);
        $this->assertEquals(401, $response->data['data']['status']);
    }

    /**
     * Test the wildcard for all endpoints
     *
     * @return void
     *
     * @access public
     * @version 6.4.0
     */
    public function testAllWildCardMatch()
    {
        $object = AAM::getUser()->getObject(AAM_Core_Object_Route::OBJECT_TYPE);
        $this->assertTrue($object->updateOptionItem('restful|*|*', true)->save());

        // Reset all internal cache
        $this->_resetSubjects();

        $request  = new WP_REST_Request('GET', '/wp/v2/posts');
        $server   = rest_get_server();
        $response = $server->dispatch($request);

        $this->assertEquals('WP_REST_Response', get_class($response));
        $this->assertEquals('rest_access_denied', $response->data['code']);
        $this->assertEquals('Access Denied', $response->data['message']);
        $this->assertEquals(401, $response->data['data']['status']);
    }

}