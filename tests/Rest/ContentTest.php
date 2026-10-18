<?php

declare(strict_types=1);

namespace AAM\UnitTest\Rest;

use AAM\UnitTest\Utility\TestCase;

final class ContentTest extends TestCase
{
    public function testBrowsePostsIncludesAllActiveStatusesAndMatchesCount(): void
    {
        $title = 'AAM browse status ' . uniqid();
        $expected = [];
        $statuses = [];

        foreach ([ 'publish', 'draft', 'pending', 'future', 'private' ] as $status) {
            $post = [
                'post_type'   => 'page',
                'post_status' => $status,
                'post_title'  => $title . ' ' . $status
            ];
            if ($status === 'future') {
                $post['post_date'] = date('Y-m-d H:i:s', strtotime('+2 days'));
            }
            $post_id = $this->createPost($post);
            $expected[] = $post_id;
            $statuses[$post_id] = $status;
        }

        $trashed = $this->createPost([
            'post_type'   => 'page',
            'post_status' => 'trash',
            'post_title'  => $title . ' trash'
        ]);

        $response = rest_get_server()->dispatch($this->prepareRestRequest(
            'GET', '/aam/v2/posts', [ 'query_params' => [
                'post_type' => 'page',
                'search'    => $title,
                'per_page'  => 20,
                'offset'    => 0,
                'access_level' => 'role',
                'role_id'      => 'subscriber'
            ] ]
        ));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $ids = array_map(static function ($post) { return $post['id']; }, $data['list']);

        $this->assertEqualsCanonicalizing($expected, $ids);
        $this->assertNotContains($trashed, $ids);
        $this->assertSame(count($expected), (int) $data['summary']['filtered_count']);
        foreach ($data['list'] as $post) {
            $this->assertSame($statuses[$post['id']], $post['post_status']);
            $this->assertSame(
                get_post_status_object($post['post_status'])->label,
                $post['post_status_label']
            );
        }
    }

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
