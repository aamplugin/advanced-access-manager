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
 * Test the naming collision use-cases
 *
 * @version 6.9.24
 */
class PolicyNamingCollisionTest extends TestCase
{

    use ResetTrait;

    /**
     * Test Equals naming collision
     *
     * @return void
     *
     * @access public
     */
    public function testEqualsCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "Equals": {
                    "${PHP_GLOBAL.hello}": [
                        "test",
                        "blah"
                    ]
                }
            }
        }', true);

        $GLOBALS['hello'] = "nope";

        $this->assertFalse($manager->evaluate($policy['Condition']));

        $GLOBALS['hello'] = "blah";

        $this->assertTrue($manager->evaluate($policy['Condition']));

        unset($GLOBALS['hello']);
    }

    /**
     * Test In naming collision
     *
     * @return void
     *
     * @access public
     */
    public function testInCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "In": {
                    "(*array)[2,3]": [
                        [2, 6, 3],
                        [1, 2, 0]
                    ]
                }
            }
        }', true);

        $this->assertTrue($manager->evaluate($policy['Condition']));

        $policy = json_decode('{
            "Condition": {
                "In": {
                    "Operator": "AND",
                    "(*array)[2,3]": [
                        [2, 6, 3],
                        [1, 2, 0]
                    ]
                }
            }
        }', true);

        $this->assertFalse($manager->evaluate($policy['Condition']));

        $policy = json_decode('{
            "Condition": {
                "In": {
                    "Operator": "AND",
                    "(*array)[2,3]": [
                        [2, 6, 3],
                        [6, 3, 0, 4, 1, 2]
                    ]
                }
            }
        }', true);

        $this->assertTrue($manager->evaluate($policy['Condition']));
    }

    /**
     * Test Between naming collision
     *
     * @return void
     *
     * @access public
     */
    public function testBetweenCondition()
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $policy = json_decode('{
            "Condition": {
                "Between": {
                    "4": [
                        [1, 9],
                        [10, 13]
                    ]
                }
            }
        }', true);

        $this->assertTrue($manager->evaluate($policy['Condition']));

        $policy = json_decode('{
            "Condition": {
                "Between": {
                    "Operator": "AND",
                    "4": [
                        [1, 9],
                        [10, 13]
                    ]
                }
            }
        }', true);

        $this->assertFalse($manager->evaluate($policy['Condition']));
    }

}