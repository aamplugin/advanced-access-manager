<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM_Backend_View_Helper,
    AAM_Core_Policy_Validator,
    PHPUnit\Framework\TestCase;

/**
 * Test policy validator
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyValidationTest extends TestCase
{
    /**
     * Test that error is triggered when policy is empty
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEmptyPolicy()
    {
        $validator = new AAM_Core_Policy_Validator('[]');

        $this->assertEquals(array(
            __('The policy document is empty', AAM_KEY)
        ), $validator->validate());
    }

    /**
     * Test that error is triggered when policy contains invalid JSON
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testInvalidJsonPolicy()
    {
        $validator = new AAM_Core_Policy_Validator('--');

        $this->assertEquals(array(
            __('The policy is not valid JSON object', AAM_KEY)
        ), $validator->validate());
    }

    /**
     * Test that error is triggered when missing dependency
     *
     * @return void
     *
     * @access public
     * @version 6.2.0
     */
    public function testMissingDependencyPolicy()
    {
        $validator = new AAM_Core_Policy_Validator('{
            "Dependency": {
                "advanced-access-manager-x": "^1.0.0"
            }
        }');

        $this->assertEquals(array(
                "The advanced-access-manager-x is required"
        ), $validator->validate());
    }

    /**
     * Test that error is triggered when missing dependency (extended version)
     *
     * @return void
     *
     * @access public
     * @version 6.2.0
     */
    public function testMissingDependencyPolicyExtended()
    {
        $validator = new AAM_Core_Policy_Validator('{
            "Dependency": {
                "advanced-access-manager-x": {
                    "Name": "AAM X",
                    "URL": "https://aamplugin.com",
                    "Version": "^1.0.0"
                }
            }
        }');

        $this->assertEquals(array(
                "The <a href=\"https://aamplugin.com\" target=\"_blank\">AAM X</a> is required"
        ), $validator->validate());
    }

    /**
     * Test that error is triggered when dependency version is not satisfied
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testLowDependencyPolicy()
    {
        $validator = new AAM_Core_Policy_Validator('{
            "Dependency": {
                "advanced-access-manager": "<6.0.0"
            }
        }');

        $this->assertEquals(array(
            'The advanced-access-manager is not active or does not satisfy minimum required version'
        ), $validator->validate());
    }

    /**
     * Test that there is no error when everything is ok
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testValidDependencyPolicy()
    {
        $validator = new AAM_Core_Policy_Validator('{
            "Dependency": {
                "advanced-access-manager": ">=' . AAM_VERSION . '"
            }
        }');

        $this->assertEquals(0, count($validator->validate()));
    }

}