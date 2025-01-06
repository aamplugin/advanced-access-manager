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
    AAM\UnitTest\Utility\TestCase;

/**
 * Test resource type aggregations
 */
final class AggregateTest extends TestCase
{

    // /**
    //  * Making sure that aggregate take access policies Post resource into
    //  * consideration
    //  *
    //  * @return void
    //  */
    // public function testPostAggregationWithPolicy()
    // {
    //     $post_a = $this->createPost();
    //     $post_b = $this->createPost([ 'post_name' => 'test-post' ]);

    //     // Create a policy that hides 2 posts
    //     $this->assertIsInt(AAM::api()->policies()->create('{
    //         "Statement": [
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "Post:post:' . $post_a . '",
    //                 "Action": "List"
    //             },
    //             {
    //                 "Effect": "deny",
    //                 "Resource": [
    //                     "Post:post:test-post"
    //                 ],
    //                 "Action": "List",
    //                 "On": [ "backend", "api" ]
    //             }
    //         ]
    //     }'));

    //     // Making sure that the aggregator is working as expected
    //     $aggregate = AAM::api()->user()->get_resource(
    //         AAM_Framework_Type_Resource::AGGREGATE,
    //         AAM_Framework_Type_Resource::POST
    //     );

    //     $this->assertEquals([
    //         "{$post_a}|post" => [
    //             'list' => [
    //                 'effect' => 'deny',
    //                 'on'     => [ 'frontend', 'backend', 'api' ]
    //             ]
    //         ],
    //         "{$post_b}|post" => [
    //             'list' => [
    //                 'effect' => 'deny',
    //                 'on'     => [ 'backend', 'api' ]
    //             ]
    //         ]
    //     ], $aggregate->get_permissions());
    // }

    // /**
    //  * Testing aggregation for roles
    //  *
    //  * @return void
    //  */
    // public function testRoleAggregation()
    // {
    //     // Get service
    //     $service = AAM::api()->identities();

    //     $this->assertTrue($service->role('author')->deny('list_role'));
    //     $this->assertTrue($service->role('subscriber')->deny('list_role'));

    //     // Making sure that the aggregator is working as expected
    //     $aggregate = AAM::api()->user()->get_resource(
    //         AAM_Framework_Type_Resource::AGGREGATE,
    //         AAM_Framework_Type_Resource::ROLE
    //     );

    //     $this->assertEquals([
    //         'author' => [
    //             'list_role' => [
    //                 'effect' => 'deny'
    //             ]
    //         ],
    //         'subscriber' => [
    //             'list_role' => [
    //                 'effect' => 'deny'
    //             ]
    //         ]
    //     ], $aggregate->get_permissions());
    // }

    // /**
    //  * Making sure that aggregate take access policies Role resource into
    //  * consideration
    //  *
    //  * @return void
    //  */
    // public function testRoleAggregationWithPolicy()
    // {
    //     // Create a policy that hides 2 posts
    //     $this->assertIsInt(AAM::api()->policies()->create('{
    //         "Statement": [
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "Role:subscriber",
    //                 "Action": "List"
    //             },
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "Role:author:users",
    //                 "Action": "List"
    //             }
    //         ]
    //     }'));

    //     // Making sure that the aggregator is working as expected
    //     $aggregate = AAM::api()->user()->get_resource(
    //         AAM_Framework_Type_Resource::AGGREGATE,
    //         AAM_Framework_Type_Resource::ROLE
    //     );

    //     $this->assertEquals([
    //         "subscriber" => [
    //             'list_role' => [
    //                 'effect' => 'deny'
    //             ]
    //         ],
    //         "author" => [
    //             'list_users' => [
    //                 'effect' => 'deny'
    //             ]
    //         ]
    //     ], $aggregate->get_permissions());
    // }

    // /**
    //  * Test user aggregation
    //  *
    //  * @return void
    //  */
    // public function testUserAggregation()
    // {
    //     $user_a = $this->createUser();
    //     $user_b = $this->createUser();

    //     // Get service
    //     $service = AAM::api()->identities();

    //     $this->assertTrue($service->user($user_a)->deny('list_user'));
    //     $this->assertTrue($service->user($user_b)->deny('list_user'));

    //     // Making sure that the aggregator is working as expected
    //     $aggregate = AAM::api()->user()->get_resource(
    //         AAM_Framework_Type_Resource::AGGREGATE,
    //         AAM_Framework_Type_Resource::USER
    //     );

    //     $this->assertEquals([
    //         $user_a => [
    //             'list_user' => [
    //                 'effect' => 'deny'
    //             ]
    //         ],
    //         $user_b => [
    //             'list_user' => [
    //                 'effect' => 'deny'
    //             ]
    //         ]
    //     ], $aggregate->get_permissions());
    // }

    // /**
    //  * Test user aggregation with access policies
    //  *
    //  * @return void
    //  */
    // public function testUserAggregationWithPolicy()
    // {
    //     $user_a = $this->createUser();
    //     $user_b = $this->createUser([ 'user_login' => 'test_user_b' ]);
    //     $user_c = $this->createUser([ 'user_email' => 'test@aamportal.com' ]);

    //     // Create a policy that hides 2 posts
    //     $this->assertIsInt(AAM::api()->policies()->create('{
    //         "Statement": [
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "User:' . $user_a . '",
    //                 "Action": "List"
    //             },
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "User:test_user_b",
    //                 "Action": "List"
    //             },
    //             {
    //                 "Effect": "deny",
    //                 "Resource": "User:test@aamportal.com",
    //                 "Action": "List"
    //             }
    //         ]
    //     }'));

    //     // Making sure that the aggregator is working as expected
    //     $aggregate = AAM::api()->user()->get_resource(
    //         AAM_Framework_Type_Resource::AGGREGATE,
    //         AAM_Framework_Type_Resource::USER
    //     );

    //     $this->assertEquals([
    //         $user_a => [
    //             'list_user' => [
    //                 'effect' => 'deny'
    //             ]
    //         ],
    //         $user_b => [
    //             'list_user' => [
    //                 'effect' => 'deny'
    //             ]
    //         ],
    //         $user_c => [
    //             'list_user' => [
    //                 'effect' => 'deny'
    //             ]
    //         ]
    //     ], $aggregate->get_permissions());
    // }

}