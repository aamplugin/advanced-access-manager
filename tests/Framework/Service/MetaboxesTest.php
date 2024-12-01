<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Metaboxes" framework service
 */
final class MetaboxesTest extends TestCase
{

    /**
     * Test we can set permissions for metaboxes
     *
     * @return void
     */
    public function testSettingPermissions()
    {
        $service = AAM::api()->metaboxes('role:author');

        // Set permission
        $this->assertTrue($service->restrict('page_postimagediv'));

        // Assert that the metabox is restricted
        $this->assertTrue($service->is_restricted('page_postimagediv'));
        $this->assertFalse($service->is_allowed('page_postimagediv'));

        // Set permission
        $this->assertTrue($service->allow('page_pageparentdiv'));

        // Assert that the metabox is restricted
        $this->assertFalse($service->is_restricted('page_pageparentdiv'));
        $this->assertTrue($service->is_allowed('page_pageparentdiv'));

    }

}