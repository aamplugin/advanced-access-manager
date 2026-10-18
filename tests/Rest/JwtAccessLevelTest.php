<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM;
use AAM\UnitTest\Utility\TestCase;
use WP_REST_Request;

/**
 * JWT routes require an authorized user access level
 *
 * @package AAM
 * @version 8.0.2
 */
final class JwtAccessLevelTest extends TestCase
{
    /**
     * An omitted access level cannot bypass target-user authorization
     *
     * @return void
     * @access public
     *
     * @version 8.0.2
     */
    public function testDelegatedJwtManagerCannotIssueAdministratorToken(): void
    {
        $role_slug = 'unit_jwt_delegated_editor';
        $capabilities = wp_roles()->get_role('editor')->capabilities;
        $capabilities['aam_manager'] = true;
        $capabilities['aam_manage_jwt'] = true;
        $role = wp_roles()->add_role($role_slug, 'JWT Delegated Editor', $capabilities);

        try {
            $editor_id = $this->createUser([ 'role' => $role_slug ]);
            $admin_id = $this->createUser([ 'role' => 'administrator' ]);
            wp_set_current_user($editor_id);

            $this->assertTrue(current_user_can('aam_manager'));
            $this->assertTrue(current_user_can('aam_manage_jwt'));
            $this->assertFalse(current_user_can('aam_manage_users'));
            $this->assertFalse(current_user_can('edit_user', $admin_id));

            $before = get_user_option('aam_jwt_registry', $admin_id);
            $missing_level = $this->dispatchCreateToken($admin_id);

            $this->assertSame(400, $missing_level->get_status());
            $this->assertSame(
                'rest_missing_callback_param', $missing_level->get_data()['code']
            );
            $this->assertContains(
                'access_level', $missing_level->get_data()['data']['params']
            );
            $this->assertSame($before, get_user_option('aam_jwt_registry', $admin_id));

            $read_request = new WP_REST_Request('GET', '/aam/v2/jwts');
            $read_request->set_query_params([ 'user_id' => $admin_id ]);
            $missing_level_read = rest_get_server()->dispatch($read_request);
            $this->assertSame(400, $missing_level_read->get_status());
            $this->assertContains(
                'access_level', $missing_level_read->get_data()['data']['params']
            );

            $wrong_level = $this->dispatchCreateToken($admin_id, 'default');
            $this->assertSame(400, $wrong_level->get_status());
            $this->assertArrayHasKey(
                'access_level', $wrong_level->get_data()['data']['params']
            );
            $this->assertSame($before, get_user_option('aam_jwt_registry', $admin_id));

            $explicit_level = $this->dispatchCreateToken($admin_id, 'user');
            $this->assertSame(400, $explicit_level->get_status());
            $this->assertSame(
                'rest_forbidden',
                $explicit_level->get_data()['data']['details']['access_level']['code']
            );
            $this->assertSame($before, get_user_option('aam_jwt_registry', $admin_id));

            $role->add_cap('aam_manage_users');
            wp_set_current_user(0);
            wp_set_current_user($editor_id);
            $this->assertTrue(current_user_can('aam_manage_users'));
            $this->assertFalse(current_user_can('edit_user', $admin_id));

            $inaccessible_user = $this->dispatchCreateToken($admin_id, 'user');
            $this->assertSame(400, $inaccessible_user->get_status());
            $this->assertSame(
                'rest_not_found',
                $inaccessible_user->get_data()['data']['details']['user_id']['code']
            );
            $this->assertSame($before, get_user_option('aam_jwt_registry', $admin_id));
        } finally {
            wp_roles()->remove_role($role_slug);
        }
    }

    /**
     * Routes with the general access-level schema retain optional context
     *
     * @return void
     * @access public
     *
     * @version 8.0.2
     */
    public function testGeneralAccessLevelRoutesRemainOptional(): void
    {
        $admin_id = $this->createUser([ 'role' => 'administrator' ]);
        $admin = new \WP_User($admin_id);
        $admin->add_cap('aam_manager');
        $admin->add_cap('aam_manage_settings');
        wp_set_current_user($admin_id);

        $request = new WP_REST_Request('GET', '/aam/v2/settings');
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
    }

    /**
     * An authorized request can still create a token for its selected user
     *
     * @return void
     * @access public
     *
     * @version 8.0.2
     */
    public function testAuthorizedJwtRequestStillWorks(): void
    {
        $admin_id = $this->createUser([ 'role' => 'administrator' ]);
        $admin = new \WP_User($admin_id);
        $admin->add_cap('aam_manager');
        $admin->add_cap('aam_manage_jwt');
        $admin->add_cap('aam_manage_users');
        wp_set_current_user($admin_id);

        $response = $this->dispatchCreateToken($admin_id, 'user');

        $this->assertSame(200, $response->get_status(), wp_json_encode($response->get_data()));
        $this->assertTrue($response->get_data()['is_valid']);
        $token = $response->get_data()['token'];
        $this->assertSame($admin_id, AAM::api()->jwt->decode($token)['user_id']);
        $this->assertTrue(AAM::api()->jwts('user:' . $admin_id)->validate($token));
    }

    /**
     * Build and dispatch a JWT creation request
     *
     * @param int         $user_id
     * @param string|null $access_level [Optional]
     *
     * @return \WP_REST_Response
     * @access private
     *
     * @version 8.0.2
     */
    private function dispatchCreateToken(int $user_id, ?string $access_level = null)
    {
        $request = new WP_REST_Request('POST', '/aam/v2/jwts');
        $query = [ 'user_id' => $user_id ];

        if ($access_level !== null) {
            $query['access_level'] = $access_level;
        }

        $request->set_query_params($query);
        $request->set_body_params([ 'expires_in' => '+1 hour' ]);

        return rest_get_server()->dispatch($request);
    }
}
