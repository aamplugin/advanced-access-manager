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

        $stub = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, true),
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Check if save returns positive result
        $this->assertEquals(
            $stub->save(), wp_json_encode(array('status' => 'success'))
        );

        // Verify that created capability actually is inside the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertTrue(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );

        $this->assertTrue($option['subscriber']['capabilities']['aam_test_cap_a']);
    }

    /**
     * Test if capabilities can be added properly for the defined role and also
     * current user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testAssignCapabilityToRoleAndCurrentUser()
    {
        global $wpdb;

        $stub = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_c'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, true),
                array('assignToMe', FILTER_VALIDATE_BOOLEAN, 0, true)
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Check if save returns positive result
        $this->assertEquals(
            $stub->save(), wp_json_encode(array('status' => 'success'))
        );

        // Verify that created capability actually is inside the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertTrue(
            array_key_exists('aam_test_cap_c', $option['subscriber']['capabilities'])
        );

        $this->assertTrue($option['subscriber']['capabilities']['aam_test_cap_c']);

        $this->assertTrue(AAM::getUser()->hasCapability('aam_test_cap_c'));

        // Clean-up after execution
        AAM::getUser()->removeCapability('aam_test_cap_c');
        $stub->delete();
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

        $stub = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, false),
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Check if save returns positive result
        $this->assertEquals(
            $stub->save(), wp_json_encode(array('status' => 'success'))
        );

        // Verify that created capability actually is inside the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertTrue(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );

        $this->assertFalse($option['subscriber']['capabilities']['aam_test_cap_a']);
    }

    /**
     * Test if capabilities can be deleted from the very specific role
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testCapabilityDeletionFromRole()
    {
        global $wpdb;

        $stub = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, true),
                array('subjectOnly', FILTER_VALIDATE_BOOLEAN, 0, true)
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Insert the test capability before it'll be deleted
        $stub->save();

        // Delete the test capability from the subject
        $this->assertEquals(
            $stub->delete(), wp_json_encode(array('status' => 'success'))
        );

        // Confirm that deleted capability is no longer in the subscriber role
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
        global $wpdb;

        // Prepare and insert test capability for the "subscriber" editor
        $stubA = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, true),
                array('subjectOnly', FILTER_VALIDATE_BOOLEAN, 0, false)
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );
        // Insert the test capability before it'll be deleted
        $this->assertEquals(
            $stubA->save(), wp_json_encode(array('status' => 'success'))
        );

        // Prepare and insert test capability for the "editor" role
        $stubB = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, true)
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('editor');
            }
        );
        // Insert the test capability before it'll be deleted
        $this->assertEquals(
            $stubB->save(), wp_json_encode(array('status' => 'success'))
        );

        // Delete the test capability from all roles
        $this->assertEquals(
            $stubA->delete(), wp_json_encode(array('status' => 'success'))
        );

        // Confirm that deleted capability is no longer in the subscriber & editor
        // roles
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertFalse(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );

        $this->assertFalse(
            array_key_exists('aam_test_cap_a', $option['editor']['capabilities'])
        );
    }

    /**
     * Test if capabilities can be updated properly for the defined subject
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testUpdateCapability()
    {
        global $wpdb;

        $stubA = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('effect', FILTER_VALIDATE_BOOLEAN, 0, false),
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Check if save returns positive result
        $this->assertEquals(
            $stubA->save(), wp_json_encode(array('status' => 'success'))
        );

        // Create a new stub that will update the test capability
        $stubB = $this->prepareRoleStub(
            // Create a map of arguments to return values
            array(
                array('capability', FILTER_DEFAULT, 0, 'aam_test_cap_a'),
                array('updated', FILTER_DEFAULT, 0, 'aam_test_cap_b')
            ),
            // Subject callback
            function() {
                return new AAM_Core_Subject_Role('subscriber');
            }
        );

        // Check if save returns positive result
        $this->assertEquals(
            $stubB->update(), wp_json_encode(array('status' => 'success'))
        );

        // Verify that capability actually is updated the user_roles option
        $option = get_option(sprintf('%suser_roles', $wpdb->prefix));

        $this->assertFalse(
            array_key_exists('aam_test_cap_a', $option['subscriber']['capabilities'])
        );

        $this->assertTrue(
            array_key_exists('aam_test_cap_b', $option['subscriber']['capabilities'])
        );

        $this->assertFalse($option['subscriber']['capabilities']['aam_test_cap_b']);
    }

    /**
     * Prepare proper subject stub
     *
     * @param array    $paramMap
     * @param callback $callback
     *
     * @return object
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareRoleStub($paramMap, $callback)
    {
        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder(AAM_Backend_Feature_Main_Capability::class)
            ->setMethods(array('getFromPost', 'getSubject'))
            ->getMock();

        // Configure the stub
        $stub->method('getFromPost')->will($this->returnValueMap($paramMap));
        $stub->method('getSubject')->will($this->returnCallback($callback));

        return $stub;
    }

}