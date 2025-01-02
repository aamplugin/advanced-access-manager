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
 * Test class for the AAM "Backend Menu" framework resource
 */
final class BackendMenuTest extends TestCase
{

    /**
     * Testing that we can properly initialize the backend menu resource
     *
     * @return void
     */
    public function testBackendMenuResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(
            AAM::api()->backend_menu()->is_restricted('edit-tags.php?taxonomy=category')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "BackendMenu:edit-tags.php?taxonomy=category",
                "Effect": "deny"
            }
        }'));

        // Verifying that toolbar item is restricted
        $this->assertTrue(
            AAM::api()->backend_menu()->is_restricted('edit-tags.php?taxonomy=category')
        );
    }

}