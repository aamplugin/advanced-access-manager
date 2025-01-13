<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Preference;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Logout Redirect" framework preference
 */
final class LogoutRedirectTest extends TestCase
{

    /**
     * Testing that we can properly initialize the preference
     *
     * @return void
     */
    public function testPreferenceInitWithPolicy()
    {
        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Param": {
                "Key": "redirect:on:logout",
                "Value": {
                    "Type": "url_redirect",
                    "Url": "/home-page"
                }
            }
        }'));

        // Verifying preferences
        $this->assertEquals([
            'type'         => 'url_redirect',
            'redirect_url' => '/home-page'
        ], AAM::api()->logout_redirect()->get_redirect());
    }

}