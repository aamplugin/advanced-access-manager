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
 * Test class for the AAM "URL" framework resource
 */
final class UrlTest extends TestCase
{

    /**
     * Testing that we can properly initialize the URL resource
     *
     * @return void
     */
    public function testUrlResourceInit()
    {
        // Verifying that URL is allowed
        $this->assertFalse(AAM::api()->urls()->is_restricted('/test-page'));
        $this->assertFalse(AAM::api()->urls()->is_restricted('/another-page'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Url:/test-page"
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:' . home_url('/another-page') . '"
                }
            ]
        }'));

        // Making sure that permissions are properly set
        $perms = AAM::api()->user()->get_resource('url')->get_permissions();

        // Verifying that URLs are restricted
        $this->assertTrue(AAM::api()->urls()->is_restricted('/test-page'));
        $this->assertTrue(AAM::api()->urls()->is_restricted('/another-page'));

        $this->assertEquals([
            '/test-page' => [
                'effect'   => 'deny',
                'redirect' => [ 'type' => 'default' ]
            ],
            '/another-page' => [
                'effect'   => 'deny',
                'redirect' => [ 'type' => 'default' ]
            ]
        ], $perms);
    }

    /**
     * Testing that we can properly initialize the RUL resource with redirect
     *
     * @return void
     */
    public function testUrlRedirectResourceInit()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": [
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-a",
                    "Redirect": {
                        "Type": "page_redirect",
                        "Slug": "authentication-required",
                        "StatusCode": 301
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-b",
                    "Redirect": {
                        "Type": "page_redirect",
                        "Id": 76,
                        "StatusCode": 307
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-c",
                    "Redirect": {
                        "Type": "url_redirect",
                        "Url": "/different-page",
                        "StatusCode": 302
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-d",
                    "Redirect": {
                        "Type": "custom_message",
                        "Message": "You are not allowed to be here"
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-e",
                    "Redirect": {
                        "Type": "login_redirect"
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-f",
                    "Redirect": {
                        "Type": "trigger_callback",
                        "Callback": "do_redirect_or_return_redirect_url_func"
                    }
                },
                {
                    "Effect": "deny",
                    "Resource": "Url:/page-g"
                }
            ]
        }'));

        // Making sure that permissions are properly set
        $perms = AAM::api()->user()->get_resource('url')->get_permissions();

        $this->assertEquals([
            '/page-a' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type'               => 'page_redirect',
                    'redirect_page_slug' => 'authentication-required',
                    'http_status_code'   => 301
                ]
            ],
            '/page-b' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type'             => 'page_redirect',
                    'redirect_page_id' => 76,
                    'http_status_code' => 307
                ]
            ],
            '/page-c' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type'             => 'url_redirect',
                    'redirect_url'     => '/different-page',
                    'http_status_code' => 302
                ]
            ],
            '/page-d' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type'    => 'custom_message',
                    'message' => 'You are not allowed to be here'
                ]
            ],
            '/page-e' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type' => 'login_redirect',
                ]
            ],
            '/page-f' => [
                'effect'   => 'deny',
                'redirect' => [
                    'type'     => 'trigger_callback',
                    'callback' => 'do_redirect_or_return_redirect_url_func',
                ]
            ],
            '/page-g' => [
                'effect'   => 'deny',
                'redirect' => [ 'type' => 'default' ]
            ]
        ], $perms);
    }

}