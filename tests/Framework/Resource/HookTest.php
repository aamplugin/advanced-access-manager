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
 * Test class for the AAM "Hook" framework resource
 */
final class HookTest extends TestCase
{

    /**
     * Test to ensure that we can properly initialize "deny" hook with JSON
     * access policy
     *
     * @return void
     */
    public function testDenyEffectWithPolicy()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Hook:admin_menu"
                },
                {
                    "Effect": "deny",
                    "Resource": "Hook:aam_test_filter:14"
                }
            ]
        }'));

        // Making sure that Hook resource is properly initialized
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );

        $this->assertEquals([
            'admin_menu|10' => [
                'access' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_test_filter|14' => [
                'access' => [
                    'effect'         => 'deny',
                    '__access_level' => 'visitor'
                ]
            ]
        ], $resource->get_permissions());
    }

    /**
     * Test to ensure that we can properly initialize "alter" hook with JSON
     * access policy
     *
     * @return void
     */
    public function testAlterEffectWithPolicy()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "alter",
                    "Resource": "Hook:screen_options_show_screen",
                    "Return": false
                },
                {
                    "Effect": "alter",
                    "Resource": "Hook:aam_test_filter:11",
                    "Return": "test"
                }
            ]
        }'));

        // Making sure that Hook resource is properly initialized
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );

        $this->assertEquals([
            'screen_options_show_screen|10' => [
                'access' => [
                    'effect'         => 'alter',
                    'return'         => false,
                    '__access_level' => 'visitor'
                ]
            ],
            'aam_test_filter|11' => [
                'access' => [
                    'effect'         => 'alter',
                    'return'         => 'test',
                    '__access_level' => 'visitor'
                ]
            ]
        ], $resource->get_permissions());
    }

    /**
     * Test to ensure that we can properly initialize "merge" hook with JSON
     * access policy
     *
     * @return void
     */
    public function testMergeEffectWithPolicy()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "merge",
                    "Resource": "Hook:allowed_redirect_hosts",
                    "MergeWith": [
                        "members.aamportal.com",
                        "store.aamportal.com"
                    ]
                }
            ]
        }'));

        // Making sure that Hook resource is properly initialized
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );

        $this->assertEquals([
            'allowed_redirect_hosts|10' => [
                'access' => [
                    'effect' => 'merge',
                    'return' => [
                        'members.aamportal.com',
                        'store.aamportal.com'
                    ],
                    '__access_level' => 'visitor'
                ]
            ]
        ], $resource->get_permissions());
    }

    /**
     * Test to ensure that we can properly initialize "replace" hook with JSON
     * access policy
     *
     * @return void
     */
    public function testReplaceEffectWithPolicy()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Effect": "replace",
                "Resource": "Hook:show_password_fields",
                "Return": false
            }
        }'));

        // Making sure that Hook resource is properly initialized
        $resource = AAM::api()->user()->get_resource(
            AAM_Framework_Type_Resource::HOOK
        );

        $this->assertEquals([
            'show_password_fields|10' => [
                'access' => [
                    'effect'         => 'replace',
                    'return'         => false,
                    '__access_level' => 'visitor'
                ]
            ]
        ], $resource->get_permissions());
    }

}