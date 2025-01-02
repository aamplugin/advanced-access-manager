<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM,
    AAM_Framework_Type_Resource,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Aggregate" framework resource
 */
final class AggregateTest extends TestCase
{

    /**
     * Testing aggregation for posts
     *
     * @return void
     */
    public function testPostAggregation()
    {
        $post_a = $this->createPost();
        $post_b = $this->createPost();

        // Get service
        $service = AAM::api()->content();

        $this->assertTrue($service->post($post_a)->add_permission('list'));
        $this->assertTrue($service->post($post_b)->add_permission('list'));

        // Making sure that the aggregator is working as expected
        $aggregate = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::AGGREGATE,
            AAM_Framework_Type_Resource::POST
        );

        $this->assertEquals([
            "{$post_a}|post" => [
                'list' => [
                    'effect' => 'deny',
                    'on'     => [ 'frontend', 'backend', 'api' ]
                ]
            ],
            "{$post_b}|post" => [
                'list' => [
                    'effect' => 'deny',
                    'on'     => [ 'frontend', 'backend', 'api' ]
                ]
            ]
        ], $aggregate->get_permissions());
    }

    /**
     * Making sure that aggregate take access policies Post resource into
     * consideration
     *
     * @return void
     */
    public function testPostAggregationWithPolicy()
    {
        $post_a = $this->createPost();
        $post_b = $this->createPost([ 'post_name' => 'test-post' ]);

        // Create a policy that hides 2 posts
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Post:post:' . $post_a . '",
                    "Action": "List"
                },
                {
                    "Effect": "deny",
                    "Resource": [
                        "Post:post:test-post"
                    ],
                    "Action": "List",
                    "On": [ "backend", "api" ]
                }
            ]
        }'));

        // Making sure that the aggregator is working as expected
        $aggregate = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::AGGREGATE,
            AAM_Framework_Type_Resource::POST
        );

        $this->assertEquals([
            "{$post_a}|post" => [
                'list' => [
                    'effect' => 'deny',
                    'on'     => [ 'frontend', 'backend', 'api' ]
                ]
            ],
            "{$post_b}|post" => [
                'list' => [
                    'effect' => 'deny',
                    'on'     => [ 'backend', 'api' ]
                ]
            ]
        ], $aggregate->get_permissions());
    }

    public function testRoleAggregation()
    {

    }

    public function testRoleAggregationWithPolicy()
    {

    }

    public function testUserAggregation()
    {

    }

    public function testUserAggregationWithPolicy()
    {

    }

}