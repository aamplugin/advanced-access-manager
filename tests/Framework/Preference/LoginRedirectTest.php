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
 * Test class for the AAM "Login Redirect" framework preference
 */
final class LoginRedirectTest extends TestCase
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
                "Key": "redirect:on:login",
                "Value": {
                    "Type": "trigger_callback",
                    "Callback": "do_login_workflow"
                }
            }
        }'));

        // Verifying preferences
        $this->assertEquals([
            'type' => 'trigger_callback',
            'callback' => 'do_login_workflow'
        ], AAM::api()->login_redirect()->get_redirect());
    }

}