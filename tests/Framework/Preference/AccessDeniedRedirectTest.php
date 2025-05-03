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
 * Test class for the AAM "Access Denied" framework preference
 */
final class AccessDeniedRedirectTest extends TestCase
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
                "Key": "redirect:on:access-denied:frontend",
                "Value": {
                    "Type": "page_redirect",
                    "Id": 2
                }
            }
        }'));

        // Verifying preferences
        $this->assertEquals([
            'type' => 'page_redirect',
            'redirect_page_id' => 2
        ], AAM::api()->access_denied_redirect()->get_redirect('frontend'));
        $this->assertEquals([
            'type' => 'default'
        ], AAM::api()->access_denied_redirect()->get_redirect('backend'));
    }

}