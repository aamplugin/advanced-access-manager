<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Utility;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Config" framework utility
 */
final class ConfigTest extends TestCase
{

    /**
     * Testing the getting configuration part
     *
     * @return void
     */
    public function testGetSetConfig()
    {
        // Add some configurations to DB
        $this->assertTrue(
            AAM::api()->config->set('core.settings.xmlrpc_enabled', false)
        );
        $this->assertFalse(
            AAM::api()->config->get('core.settings.xmlrpc_enabled')
        );

        // Read raw data from DB and ensure the key is stored properly
        $raw = $this->readWpOption(AAM::api()->config::DB_OPTION);

        $this->assertIsArray($raw);
        $this->assertArrayHasKey('core.settings.xmlrpc_enabled', $raw);
        $this->assertFalse($raw['core.settings.xmlrpc_enabled']);
    }

    /**
     * Testing that we can reset configurations correctly
     *
     * @return void
     */
    public function testResetConfig()
    {
        // Let's set a few configurations first
        $this->assertTrue(AAM::api()->config->set(
            'core.settings.ui.tips', false
        ));
        $this->assertTrue(AAM::api()->config->set(
            'core.settings.multi_access_levels', true
        ));

        // Assert that we can delete a specific option only
        $this->assertTrue(AAM::api()->config->reset(
            'core.settings.ui.tips'
        ));

        // Verifying that we back to default and settings are properly stored in DB
        $this->assertTrue(AAM::api()->config->get(
            'core.settings.ui.tips'
        ));

        $raw = $this->readWpOption(AAM::api()->config::DB_OPTION);

        $this->assertIsArray($raw);
        $this->assertArrayNotHasKey('core.settings.ui.tips', $raw);

        // Resettings all configurations
        $this->assertTrue(AAM::api()->config->reset());

        // Verifying settings
        $this->assertFalse(AAM::api()->config->get(
            'core.settings.multi_access_levels'
        ));

        $raw = $this->readWpOption(AAM::api()->config::DB_OPTION);

        $this->assertIsArray($raw);
        $this->assertEmpty($raw);
    }

}