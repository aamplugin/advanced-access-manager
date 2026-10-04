<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM\UnitTest\Utility\TestCase;

final class ContentTest extends TestCase
{
    public function testBatchPostUpdatePreservesOtherExplicitPermissions(): void
    {
        $post_id = $this->createPost();
        $server = rest_get_server();
        $path = '/aam/v2/post/' . $post_id;
        $query = [ 'access_level' => 'role', 'role_id' => 'subscriber' ];

        $first = $server->dispatch($this->prepareRestRequest('POST', $path, [
            'query_params' => $query,
            'post_params' => [ 'permissions' => [
                [ 'permission' => 'read', 'effect' => 'deny' ],
                [ 'permission' => 'edit', 'effect' => 'deny' ]
            ] ]
        ]));

        $this->assertSame(200, $first->get_status());
        $this->assertSame('deny', $first->get_data()['explicit_permissions']['read']['effect']);
        $this->assertSame('deny', $first->get_data()['explicit_permissions']['edit']['effect']);

        $second = $server->dispatch($this->prepareRestRequest('POST', $path, [
            'query_params' => $query,
            'post_params' => [ 'permissions' => [
                [ 'permission' => 'read', 'effect' => 'allow' ]
            ] ]
        ]));

        $this->assertSame(200, $second->get_status());
        $this->assertSame('allow', $second->get_data()['explicit_permissions']['read']['effect']);
        $this->assertSame('deny', $second->get_data()['explicit_permissions']['edit']['effect']);
    }
}
