<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Capability;

use AAM,
    AAM_Core_Subject_Role,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\AuthUserTrait,
    AAM_Backend_Feature_Main_Capability;

/**
 * Test Capability manager features
 *
 * @version 6.0.0
 */
class CapabilityManagerTest extends TestCase
{

    use AuthUserTrait;

    /**
     * Test if capabilities can be added properly for defined role
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAssignCapabilityToRole()
    {
        global $wpdb;

        \AAM_Framework_Manager::roles()->update_role(
            'subscriber',
            [
                'add_caps' => [
                    'aam_test_cap_a'
                ]
            ]
        );

        // Verify that created capability actually is inside the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertTrue(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );

        $this->assertTrue($option['subscriber']['capabilities']['aam_test_cap_a']);
    }

    /**
     * Test if capabilities can be deprived properly for defined role
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testDepriveCapabilityToRole()
    {
        global $wpdb;

        \AAM_Framework_Manager::roles()->update_role(
            'subscriber',
            [
                'remove_caps' => [
                    'aam_test_cap_a'
                ]
            ]
        );

        // Verify that created capability actually is inside the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertFalse(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );
    }

    /**
     * Test if capabilities can be deleted from all roles
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCapabilityDeletionFromAllRoles()
    {
        \AAM_Framework_Manager::capabilities()->delete(
            'aam_test_cap_a'
        );

        $this->assertFalse(
            \AAM_Framework_Manager::capabilities()->exists('aam_test_cap_a')
        );
    }

}