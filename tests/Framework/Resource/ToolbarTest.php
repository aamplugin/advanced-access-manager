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
 * Test class for the AAM "Toolbar" framework resource
 */
final class ToolbarTest extends TestCase
{

    /**
     * Testing that we can properly initialize the admin toolbar resource
     *
     * @return void
     */
    public function testToolbarResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(AAM::api()->admin_toolbar()->is_denied('documentation'));

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Toolbar:documentation",
                "Effect": "deny"
            }
        }'));

        // Verifying that toolbar item is restricted
        $this->assertTrue(AAM::api()->admin_toolbar()->is_denied('documentation'));
    }

}