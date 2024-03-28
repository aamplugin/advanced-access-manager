<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM_Core_Policy_Condition,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;


/**
 * Test the condition "Operator" attribute
 *
 * @version 6.9.24
 */
class PolicyConditionOperatorTest extends TestCase
{

    use ResetTrait;

    /**
     * Test "Operator" general condition as "AND"
     *
     * @return void
     *
     * @access public
     */
    public function testOperatorAndCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "Operator": "AND",
                "Equals": {
                    "${PHP_GLOBAL.hello}": "test"
                },
                "Less": {
                    "(*int)5": 7
                }
            }
        }', true);

        $GLOBALS['hello'] = "test";

        $this->assertTrue($manager->evaluate($policy['Condition']));

        unset($GLOBALS['hello']);
    }

    /**
     * Test "Operator" general condition as "OR"
     *
     * @return void
     *
     * @access public
     */
    public function testOperatorOrCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "Operator": "OR",
                "Equals": {
                    "${PHP_GLOBAL.hello}": "test"
                },
                "Less": {
                    "(*int)5": 7
                }
            }
        }', true);

        $GLOBALS['hello'] = "no";

        $this->assertTrue($manager->evaluate($policy['Condition']));

        unset($GLOBALS['hello']);
    }

    /**
     * Test "Operator" within group as "AND"
     *
     * @return void
     *
     * @access public
     */
    public function testOperatorAndWithinGroupCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "Equals": {
                    "Operator": "AND",
                    "${PHP_GLOBAL.varA}": "test",
                    "${PHP_GLOBAL.varB}": "more"
                }
            }
        }', true);

        $GLOBALS['varA'] = "test";
        $GLOBALS['varB'] = "more";

        $this->assertTrue($manager->evaluate($policy['Condition']));

        $GLOBALS['varB'] = "no";

        $this->assertFalse($manager->evaluate($policy['Condition']));

        unset($GLOBALS['varA']);
        unset($GLOBALS['varB']);
    }


}