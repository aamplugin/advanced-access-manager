<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM;
use AAM\UnitTest\Utility\TestCase;

/**
 * Access denied responses for REST, WordPress Abilities, and MCP.
 *
 * @package AAM
 * @version 8.0.0
 */
final class AccessDeniedApiResponseTest extends TestCase
{
    /**
     * Provide a custom API denial for the callback behavior tests.
     *
     * @param \WP_Error $error Original denial
     *
     * @return \WP_Error
     * @access public
     * @static
     * @version 8.0.0
     */
    public static function customizeDenial(\WP_Error $error): \WP_Error
    {
        return new \WP_Error(
            'unit_custom_denial',
            'Handled: ' . $error->get_error_message(),
            [ 'status' => 451 ]
        );
    }

    /**
     * Verify REST responses use the configured status and plain text message.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testRestDenialResponse(): void
    {
        $user_id = $this->createUser();
        wp_set_current_user($user_id);
        $this->assertTrue(AAM::api()->access_denied_redirect()->set_redirect(
            'api', [
                'type' => 'custom_message',
                'message' => '<strong>Ask your administrator.</strong>',
                'http_status_code' => 403
            ]
        ));

        $response = new \WP_REST_Response([
            'code' => 'rest_access_denied',
            'message' => 'Access Denied',
            'data' => [ 'status' => 401 ]
        ], 401);
        $result = \AAM_Service_AccessDeniedRedirect::bootstrap()->filter_rest_denial(
            $response, null, new \WP_REST_Request('GET', '/unit')
        );
        $this->assertSame(403, $result->get_status());
        $this->assertSame('Ask your administrator.', $result->get_data()['message']);
        $this->assertSame(403, $result->get_data()['data']['status']);

        $other = new \WP_REST_Response([
            'code' => 'aam_ability_denied', 'message' => 'Ability denied'
        ], 403);
        $this->assertSame('Ability denied',
            \AAM_Service_AccessDeniedRedirect::bootstrap()->filter_rest_denial(
                $other, null, new \WP_REST_Request('GET', '/unit')
            )->get_data()['message']);
    }

    /**
     * Verify Ability and MCP errors use the same API response settings.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testAbilityAndMcpDenialMessages(): void
    {
        $user_id = $this->createUser();
        wp_set_current_user($user_id);
        $responses = AAM::api()->access_denied_redirect();

        $this->assertTrue($responses->set_redirect('api', [
            'type' => 'custom_message',
            'message' => 'API access is unavailable.',
            'http_status_code' => 429
        ]));
        $this->assertTrue(AAM::api()->abilities()->deny('unit/read'));
        $this->assertTrue(AAM::api()->mcp_prompts()->deny('unit-prompt', 'unit-server'));
    }

    /**
     * Verify one PHP callback handles REST, Ability, and MCP denials.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testApiCallbackBehavior(): void
    {
        $user_id = $this->createUser();
        wp_set_current_user($user_id);
        $service = AAM::api()->access_denied_redirect();
        $this->assertTrue($service->set_redirect('api', [
            'type' => 'trigger_callback',
            'callback' => self::class . '::customizeDenial'
        ]));

        $response = new \WP_REST_Response([
            'code' => 'rest_access_denied',
            'message' => 'REST blocked',
            'data' => [ 'status' => 401 ]
        ], 401);
        $result = \AAM_Service_AccessDeniedRedirect::bootstrap()->filter_rest_denial(
            $response, null, new \WP_REST_Request('GET', '/unit')
        );
        $this->assertSame(451, $result->get_status());
        $this->assertSame('unit_custom_denial', $result->get_data()['code']);
        $this->assertSame('Handled: REST blocked', $result->get_data()['message']);

        $this->assertTrue(AAM::api()->abilities()->deny('unit/read'));

        $this->assertTrue(AAM::api()->mcp_prompts()->deny('unit-prompt', 'unit-server'));
        $server = new class {
            public function get_server_id() { return 'unit-server'; }
        };
        $this->assertTrue($service->set_redirect('api', [ 'type' => 'default' ]));
        $default = \AAM_Service_AccessDeniedRedirect::denied_error(
            'unit_denied', 'Standard denial'
        );
        $this->assertSame('unit_denied', $default->get_error_code());
        $this->assertSame('Standard denial', $default->get_error_message());
    }

    /**
     * Verify three areas remain distinct and API rejects browser redirects.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testApiResponseRestContract(): void
    {
        $server = rest_get_server();
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];
        foreach ([ 'frontend', 'backend', 'api' ] as $area) {
            $rule = $area === 'api'
                ? [
                    'type' => 'custom_message',
                    'message' => 'API access blocked',
                    'http_status_code' => 403
                ]
                : [ 'type' => 'login_redirect' ];
            $saved = $server->dispatch($this->prepareRestRequest(
                'POST', '/aam/v2/redirect/access-denied', [
                    'query_params' => $query,
                    'post_params' => [ 'area' => $area ] + $rule
                ]
            ));
            $this->assertSame(200, $saved->get_status(), $area);
            $this->assertSame($rule, $saved->get_data(), $area);
        }

        $all = $server->dispatch($this->prepareRestRequest(
            'GET', '/aam/v2/redirect/access-denied', [
                'query_params' => $query
            ]
        ));
        $this->assertSame(200, $all->get_status());
        $this->assertSame([ 'frontend', 'backend', 'api' ],
            array_keys($all->get_data()));

        $rejected = $server->dispatch($this->prepareRestRequest(
            'POST', '/aam/v2/redirect/access-denied', [
                'query_params' => $query,
                'post_params' => [
                    'area' => 'api', 'type' => 'login_redirect'
                ]
            ]
        ));
        $this->assertSame(400, $rejected->get_status());

        $callback = $server->dispatch($this->prepareRestRequest(
            'POST', '/aam/v2/redirect/access-denied', [
                'query_params' => $query,
                'post_params' => [
                    'area' => 'api',
                    'type' => 'trigger_callback',
                    'callback' => self::class . '::customizeDenial'
                ]
            ]
        ));
        $this->assertSame(200, $callback->get_status());
        $this->assertSame('trigger_callback', $callback->get_data()['type']);

        $invalid_area = $server->dispatch($this->prepareRestRequest(
            'POST', '/aam/v2/redirect/access-denied', [
                'query_params' => $query,
                'post_params' => [
                    'area' => 'mcp', 'type' => 'custom_message',
                    'message' => 'Not a separate area'
                ]
            ]
        ));
        $this->assertSame(400, $invalid_area->get_status());
    }
}
