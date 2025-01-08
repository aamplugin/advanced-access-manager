<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    WP_REST_Request,
    AAM\UnitTest\Utility\TestCase;

/**
 * Access Denied Redirect RESTful API test
 */
final class AccessDeniedRedirectTest extends TestCase
{

    public function testGetRedirect()
    {
        $server = rest_get_server();
        $result = $server->dispatch($this->_prepareRequest(
            'GET',
            '/aam/v2/service/redirect/access-denied',
            [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id'      => 'subscriber',
                    'area'         => 'frontend'
                ]
            ]
        ));

        $this->assertEquals(200, $result->get_status());
        $this->assertEquals([ 'type' => 'default' ], $result->get_data());
    }

    /**
     * Undocumented function
     *
     * @param [type] $method
     * @param [type] $endpoint
     * @param array $data
     * @return void
     */
    private function _prepareRequest($method, $endpoint, $data = [])
    {
        // Resetting user to unauthorized. User will be re-authorized with while
        // dispatching the request
        wp_set_current_user(0);

        $user_a  = $this->createUser([ 'role' => 'administrator' ]);
        $jwt     = AAM::api()->jwts('user:' . $user_a)->issue();
        $request = new WP_REST_Request($method, $endpoint);

        $request->add_header('Authorization', 'Bearer ' . $jwt['token']);

        if (isset($data['query_params'])) {
            $request->set_query_params($data['query_params']);
        }

        return $request;
    }
}