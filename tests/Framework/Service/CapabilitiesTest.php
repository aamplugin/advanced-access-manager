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
 * Test class for the AAM "Capabilities" framework service
 */
final class CapabilitiesTest extends TestCase
{

    /**
     * Making sure we can fetch the list of capabilities properly
     *
     * @return void
     */
    public function testGetAllCapabilities()
    {
        $raw     = $this->readWpOption(wp_roles()->role_key);
        $service = AAM::api()->capabilities('role:subscriber');

        // Making sure that both alias methods work properly
        $this->assertEquals($service->get_all(), $service->list());

        // Making sure that both arrays are equal
        $this->assertEquals($raw['subscriber']['capabilities'], $service->get_all());
    }

    /**
     * Testing that invalid capability cannot be added
     *
     * @return void
     */
    public function testAddInvalidCapability()
    {
        $service = AAM::api()->capabilities('role:subscriber', [
            'error_handling' => 'wp_error'
        ]);

        // Adding the invalid capability
        $error = $service->add('Test Capability');

        $this->assertEquals(get_class($error), \WP_Error::class);
        $this->assertEquals('Valid capability slug is required', $error->get_error_message());
    }

    /**
     * Testing that invalid capability can be added
     *
     * @return void
     */
    public function testSuppressedInvalidCapabilityException()
    {
        $service    = AAM::api()->capabilities('role:subscriber');
        $capability = 'Test Capability';

        // Adding the invalid capability
        $this->assertTrue($service->add($capability, true, true));

        // Asserting that capability exists
        $raw = $this->readWpOption(wp_roles()->role_key);
        $this->assertArrayHasKey($capability, $raw['subscriber']['capabilities']);
        $this->assertTrue($raw['subscriber']['capabilities'][$capability]);

        // Delete capability
        $this->assertTrue($service->remove($capability));
    }

    /**
     * Verifying that we can add capabilities
     *
     * @return void
     */
    public function testAddValidCapability()
    {
        $service = AAM::api()->capabilities('role:subscriber');
        $cap_a   = 'test_capability_a';
        $cap_b   = 'test_capability_b';

        // Adding both capability
        $this->assertTrue($service->add($cap_a, true));
        $this->assertTrue($service->add($cap_b, false));

        // Verifying both capabilities
        $raw = $this->readWpOption(wp_roles()->role_key);
        $this->assertArrayHasKey($cap_a, $raw['subscriber']['capabilities']);
        $this->assertArrayHasKey($cap_b, $raw['subscriber']['capabilities']);
        $this->assertTrue($raw['subscriber']['capabilities'][$cap_a]);
        $this->assertFalse($raw['subscriber']['capabilities'][$cap_b]);

        // Testing that we can add capability to the user directly
        $user_a  = $this->createUser([ 'role' => 'author' ]);
        $service = AAM::api()->capabilities('user:' . $user_a);

        $this->assertTrue($service->add($cap_a));

        $user = get_user($user_a);

        $this->assertArrayHasKey($cap_a, $user->caps);
        $this->assertTrue(user_can($user, $cap_a));

        // Remove all added capabilities
        $this->assertTrue($service->remove($cap_a));
        $this->assertTrue(AAM::api()->capabilities('role:subscriber')->remove($cap_a));
        $this->assertTrue(AAM::api()->capabilities('role:subscriber')->remove($cap_b));
    }

    /**
     * Testing that we can remove capability from both role & user
     *
     * @return void
     */
    public function testRemoveCapability()
    {
        $user_a = $this->createUser([ 'role' => 'author' ]);

        // Adding capability to a role & user
        $this->assertTrue(AAM::api()->capabilities('role:editor')->add('aam_test_c'));
        $this->assertTrue(AAM::api()->capabilities('user:' . $user_a)->add('aam_test_d'));

        // Asserting that we can delete them successfully
        $this->assertTrue(AAM::api()->capabilities('role:editor')->remove('aam_test_c'));
        $this->assertTrue(AAM::api()->capabilities('user:' . $user_a)->remove('aam_test_d'));
    }

    /**
     * Test that both grant and deprive methods are properly adding capabilities
     *
     * @return void
     */
    public function testGrantDepriveMethods()
    {
        $service = AAM::api()->capabilities('role:subscriber');
        $cap_a   = 'test_capability_e';
        $cap_b   = 'test_capability_f';

        // Adding both capabilities
        $this->assertTrue($service->grant($cap_a));
        $this->assertTrue($service->deprive($cap_b));

        $raw = $this->readWpOption(wp_roles()->role_key);
        $this->assertArrayHasKey($cap_a, $raw['subscriber']['capabilities']);
        $this->assertArrayHasKey($cap_b, $raw['subscriber']['capabilities']);
        $this->assertTrue($raw['subscriber']['capabilities'][$cap_a]);
        $this->assertFalse($raw['subscriber']['capabilities'][$cap_b]);

        // Remove two added caps
        $this->assertTrue($service->remove($cap_a));
        $this->assertTrue($service->remove($cap_b));
    }

    /**
     * Test a capability can be replaced successfully and retain the same effect flag
     *
     * @return void
     */
    public function testReplaceCapability()
    {
        $service = AAM::api()->capabilities('role:editor');
        $cap_a   = 'aam_test_cap_g';
        $cap_b   = 'aam_test_cap_h';

        // Adding a capability
        $this->assertTrue($service->deprive($cap_a));

        $raw = $this->readWpOption(wp_roles()->role_key);
        $this->assertArrayHasKey($cap_a, $raw['editor']['capabilities']);
        $this->assertFalse($raw['editor']['capabilities'][$cap_a]);

        // Replacing the capability
        $this->assertTrue($service->replace($cap_a, $cap_b));

        $raw = $this->readWpOption(wp_roles()->role_key);
        $this->assertArrayNotHasKey($cap_a, $raw['editor']['capabilities']);
        $this->assertArrayHasKey($cap_b, $raw['editor']['capabilities']);
        $this->assertFalse($raw['editor']['capabilities'][$cap_b]);

        // Remove the added capabilities
        $this->assertNull($service->remove($cap_a));
        $this->assertTrue($service->remove($cap_b));
    }

    /**
     * Test if exists method works properly
     *
     * @return void
     */
    public function testExists()
    {
        $service = AAM::api()->capabilities('role:subadmin');
        $cap_a   = 'test_cap_t';
        $cap_b   = 'test_cap_m';

        // Adding two capabilities with different effect
        $this->assertTrue($service->grant($cap_a));
        $this->assertTrue($service->deprive($cap_b));

        // Verify that both capabilities exist
        $this->assertTrue($service->exists($cap_a));
        $this->assertTrue($service->exists($cap_b));

        // Verify that random capability does not exist
        $this->assertFalse($service->exists(uniqid()));

        // Remove added caps
        $this->assertTrue($service->remove($cap_a));
        $this->assertTrue($service->remove($cap_b));
    }

    /**
     * Test decisions
     *
     * @return void
     */
    public function testDecisions()
    {
        $service = AAM::api()->capabilities('role:subscriber');
        $cap_a   = 'test_cap_t';
        $cap_b   = 'test_cap_m';

        // Adding two capabilities with different effect
        $this->assertTrue($service->grant($cap_a));
        $this->assertTrue($service->deprive($cap_b));

        // Verify that both capabilities exist
        $this->assertTrue($service->is_granted($cap_a));
        $this->assertFalse($service->is_deprived($cap_a));
        $this->assertFalse($service->is_granted($cap_b));
        $this->assertTrue($service->is_deprived($cap_b));

        // Remove added caps
        $this->assertTrue($service->remove($cap_a));
        $this->assertTrue($service->remove($cap_b));
    }
}