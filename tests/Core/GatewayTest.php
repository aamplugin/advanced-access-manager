<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Core;

use AAM_Core_Gateway,
    PHPUnit\Framework\TestCase;

/**
 * Test AAM core Gateway
 * 
 * @package AAM\UnitTest
 * @version 6.0.0
 */
class GatewayTest extends TestCase
{
    /**
     * Test all possible merging permutations with preference
     * 
     * @return void
     * 
     * @access public
     * @dataProvider mergingPreferenceData
     * @version 6.0.0
     */
    public function testAccessOptionsMerging($set1, $set2, $preference, $expected)
    { 
        $gateway = AAM_Core_Gateway::getInstance();

        $this->assertSame(
            $gateway->mergeSettings($set1, $set2, null, $preference), $expected
        );
    }

    /**
     * Return the array of possible access option combinations
     *
     * @return array
     * 
     * @access public
     * @version 6.0.0
     */
    public function mergingPreferenceData()
    { 
        return array(
            array(array('hidden' => true), array('hidden' => true), 'deny', array('hidden' => true)),
            array(array('hidden' => true), array('hidden' => false), 'deny', array('hidden' => true)),
            array(array('hidden' => false), array('hidden' => true), 'deny', array('hidden' => true)),
            array(array('hidden' => false), array('hidden' => false), 'deny', array('hidden' => false)),
            array(array('hidden' => true), array('hidden' => true), 'allow', array('hidden' => true)),
            array(array('hidden' => true), array('hidden' => false), 'allow', array('hidden' => false)),
            array(array('hidden' => false), array('hidden' => true), 'allow', array('hidden' => false)),
            array(array('hidden' => false), array('hidden' => false), 'allow', array('hidden' => false)),
            // One of the options is not defined
            array(array('hidden' => true), array(), 'deny', array('hidden' => true)),
            array(array('hidden' => false), array(), 'deny', array('hidden' => false)),
            array(array(), array('hidden' => true), 'deny', array('hidden' => true)),
            array(array(), array('hidden' => false), 'deny', array('hidden' => false)),
            array(array('hidden' => true), array(), 'allow', array('hidden' => false)),
            array(array('hidden' => false), array(), 'allow', array('hidden' => false)),
            array(array(), array('hidden' => true), 'allow', array('hidden' => false)),
            array(array(), array('hidden' => false), 'allow', array('hidden' => false)),
            // Complex access options that are defined as array
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array('limited' => array('enabled' => true, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => true, 'threshold' => 2))),
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array('limited' => array('enabled' => false, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => true, 'threshold' => 1))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array('limited' => array('enabled' => true, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => true, 'threshold' => 2))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array('limited' => array('enabled' => false, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => false, 'threshold' => 2))),
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array('limited' => array('enabled' => true, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => true, 'threshold' => 2))),
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array('limited' => array('enabled' => false, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => false, 'threshold' => 2))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array('limited' => array('enabled' => true, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => false, 'threshold' => 1))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array('limited' => array('enabled' => false, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => false, 'threshold' => 2))),
            // One of the options is not defined
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array(), 'deny', array('limited' => array('enabled' => true, 'threshold' => 1))),
            array(array(), array('limited' => array('enabled' => true, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => true, 'threshold' => 2))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array(), 'deny', array('limited' => array('enabled' => false, 'threshold' => 1))),
            array(array(), array('limited' => array('enabled' => false, 'threshold' => 2)), 'deny', array('limited' => array('enabled' => false, 'threshold' => 2))),
            array(array('limited' => array('enabled' => true, 'threshold' => 1)), array(), 'allow', array('limited' => array('enabled' => false, 'threshold' => 1))),
            array(array('limited' => array('enabled' => false, 'threshold' => 1)), array(), 'allow', array('limited' => array('enabled' => false, 'threshold' => 1))),
            array(array(), array('limited' => array('enabled' => true, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => false, 'threshold' => 2))),
            array(array(), array('limited' => array('enabled' => false, 'threshold' => 2)), 'allow', array('limited' => array('enabled' => false, 'threshold' => 2))),
        );
    }

}