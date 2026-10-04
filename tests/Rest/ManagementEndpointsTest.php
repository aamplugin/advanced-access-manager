<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM\UnitTest\Utility\TestCase;

/**
 * Management REST API workflows
 *
 * @package AAM
 * @version 8.0.0
 */
final class ManagementEndpointsTest extends TestCase
{
    /**
     * A newly created empty role can be selected, renamed, and deleted.
     *
     * @return void
     * @access public
     */
    public function testRoleLifecycle(): void
    {
        $server = rest_get_server();
        $slug = 'unit_rest_role';
        $path = '/aam/v2/role/' . $slug;

        $created = $server->dispatch($this->prepareRestRequest('POST', '/aam/v2/roles', [
            'post_params' => [ 'slug' => $slug, 'name' => 'REST Role' ]
        ]));
        $this->assertSame(200, $created->get_status());
        $this->assertSame($slug, $created->get_data()['slug']);

        $fetched = $server->dispatch($this->prepareRestRequest('GET', $path));
        $this->assertSame(200, $fetched->get_status());
        $this->assertSame('REST Role', $fetched->get_data()['name']);

        $updated = $server->dispatch($this->prepareRestRequest('PATCH', $path, [
            'post_params' => [ 'name' => 'Renamed REST Role' ]
        ]));
        $this->assertSame(200, $updated->get_status());
        $this->assertSame('Renamed REST Role', $updated->get_data()['name']);

        $deleted = $server->dispatch($this->prepareRestRequest('DELETE', $path));
        $this->assertSame(200, $deleted->get_status());
        $this->assertTrue($deleted->get_data()['success']);
        $this->assertNull(wp_roles()->get_role($slug));
    }

    /**
     * Capability deletion can stay local or remove grants from every role.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testCapabilityDeletionScope(): void
    {
        $capability = 'unit_capability_delete_scope';
        $first_role = 'unit_cap_delete_first';
        $second_role = 'unit_cap_delete_second';
        $roles = wp_roles();
        $roles->add_role($first_role, 'First test role', [ $capability => true ]);
        $roles->add_role($second_role, 'Second test role', [ $capability => true ]);
        $grant_management = static function ($allcaps) {
            foreach ([
                'aam_manager', 'aam_manage_capabilities',
                'aam_manage_roles', 'aam_manage_users'
            ] as $permission) {
                $allcaps[$permission] = true;
            }

            return $allcaps;
        };
        add_filter('user_has_cap', $grant_management);

        try {
            $server = rest_get_server();
            $path = '/aam/v2/capability/' . $capability;
            $local = $server->dispatch($this->prepareRestRequest('DELETE', $path, [
                'query_params' => [
                    'access_level' => 'role', 'role_id' => $first_role
                ],
                'post_params' => [ 'globally' => false ]
            ]));
            $this->assertSame(200, $local->get_status(), wp_json_encode($local->get_data()));
            $this->assertTrue($local->get_data()['success']);
            $this->assertFalse($roles->get_role($first_role)->has_cap($capability));
            $this->assertTrue($roles->get_role($second_role)->has_cap($capability));

            $roles->get_role($first_role)->add_cap($capability);
            $selected_user = $this->createUser([ 'role' => $first_role ]);
            $other_user = $this->createUser([ 'role' => $second_role ]);
            (new \WP_User($selected_user))->add_cap($capability);
            (new \WP_User($other_user))->add_cap($capability);

            $global = $server->dispatch($this->prepareRestRequest('DELETE', $path, [
                'query_params' => [
                    'access_level' => 'user', 'user_id' => $selected_user
                ],
                'post_params' => [ 'globally' => true ]
            ]));
            $this->assertSame(200, $global->get_status(), wp_json_encode($global->get_data()));
            $this->assertTrue($global->get_data()['success']);
            $this->assertFalse($roles->get_role($first_role)->has_cap($capability));
            $this->assertFalse($roles->get_role($second_role)->has_cap($capability));
            $this->assertArrayNotHasKey($capability, (new \WP_User($selected_user))->caps);
            $this->assertArrayHasKey($capability, (new \WP_User($other_user))->caps);
        } finally {
            remove_filter('user_has_cap', $grant_management);
            $roles->remove_role($first_role);
            $roles->remove_role($second_role);
        }
    }

    /**
     * User status and role filters reflect changes made through the API.
     *
     * @return void
     * @access public
     */
    public function testUserStatusAndRoleFilters(): void
    {
        $user_id = $this->createUser([ 'role' => 'subscriber' ]);
        $server = rest_get_server();
        $path = '/aam/v2/user/' . $user_id;

        $updated = $server->dispatch($this->prepareRestRequest('PATCH', $path, [
            'post_params' => [ 'status' => 'inactive' ],
            'query_params' => [ 'fields' => 'status' ]
        ]));
        $this->assertSame(200, $updated->get_status());
        $this->assertSame('inactive', $updated->get_data()['status']);

        $inactive = $server->dispatch($this->prepareRestRequest('GET', '/aam/v2/users', [
            'query_params' => [ 'status' => 'inactive', 'role' => 'subscriber', 'per_page' => 100 ]
        ]));
        $this->assertSame(200, $inactive->get_status());
        $this->assertContains($user_id, array_column($inactive->get_data()['list'], 'id'));

        $active = $server->dispatch($this->prepareRestRequest('GET', '/aam/v2/users', [
            'query_params' => [ 'status' => 'active', 'role' => 'subscriber', 'per_page' => 100 ]
        ]));
        $this->assertSame(200, $active->get_status());
        $this->assertNotContains($user_id, array_column($active->get_data()['list'], 'id'));

        $restored = $server->dispatch($this->prepareRestRequest('PATCH', $path, [
            'post_params' => [ 'status' => 'active' ],
            'query_params' => [ 'fields' => 'status' ]
        ]));
        $this->assertSame(200, $restored->get_status());
        $this->assertSame('active', $restored->get_data()['status']);
    }

    /**
     * The preload endpoint returns the selected context and services.
     *
     * @return void
     * @access public
     */
    public function testPreloadReturnsWorkspaceContext(): void
    {
        $response = rest_get_server()->dispatch($this->prepareRestRequest(
            'GET', '/aam/v2/preload', [
                'query_params' => [
                    'access_level' => 'role',
                    'role_id' => 'subscriber',
                    'screen' => 'settings'
                ]
            ]
        ));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('subscriber', $data['subject']['id']);
        $this->assertSame('settings', $data['screen']);
        $this->assertArrayHasKey('features', $data);
        $this->assertArrayHasKey('settings', $data);
    }

    /**
     * Settings can be written, read, and reset for a selected role.
     *
     * @return void
     * @access public
     */
    public function testSettingsLifecycle(): void
    {
        $server = rest_get_server();
        $path = '/aam/v2/settings';
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];
        $request = $this->prepareRestRequest('POST', $path, [
            'query_params' => $query
        ]);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode([ 'unit_test' => [ 'enabled' => true ] ]));

        $saved = $server->dispatch($request);
        $this->assertSame(200, $saved->get_status());
        $this->assertNotEmpty($saved->get_data()['success']);

        $fetched = $server->dispatch($this->prepareRestRequest('GET', $path, [
            'query_params' => $query
        ]));
        $this->assertSame(200, $fetched->get_status());
        $this->assertTrue($fetched->get_data()['unit_test']['enabled']);

        $reset = $server->dispatch($this->prepareRestRequest('DELETE', $path, [
            'query_params' => $query
        ]));
        $this->assertSame(200, $reset->get_status());
        $this->assertTrue($reset->get_data()['success']);
    }
}
