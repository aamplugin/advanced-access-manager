<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Policy_Condition,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * Framework policy condition test
 */
final class ConditionTest extends TestCase
{

    /**
     * Validate Between condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('betweenDataProvider')]
    public function testBetweenCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate Equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('equalsDataProvider')]
    public function testEqualsCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate NotEquals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('notEqualsDataProvider')]
    public function testNotEqualsCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate Greater condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('greaterDataProvider')]
    public function testGreaterCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate Less condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('lessDataProvider')]
    public function testLessCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate greater or equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('greaterOrEqualsDataProvider')]
    public function testGreaterOrEqualsCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate Less or equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('lessOrEqualsDataProvider')]
    public function testLessOrEqualsCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate In condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('inDataProvider')]
    public function testInCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate NotIn condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('notInDataProvider')]
    public function testNotInCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate Like condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('likeDataProvider')]
    public function testLikeCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate NotLike condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('notLikeDataProvider')]
    public function testNotLikeCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate RegEx condition evaluator
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('regExDataProvider')]
    public function testRegExCondition($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Validate condition type casting
     *
     * @param array   $condition
     * @param boolean $expected
     *
     * @return void
     */
    #[DataProvider('typeCastingDataProvider')]
    public function testTypeCasting($condition, $expected)
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

        $this->assertEquals($expected, $manager->execute($condition));
    }

    /**
     * Test "Operator" general condition as "AND"
     *
     * @return void
     *
     * @access public
     */
    public function testOperatorAndCondition()
    {
        $manager = AAM_Framework_Policy_Condition::get_instance();

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

        $this->assertTrue($manager->execute($policy['Condition']));

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
        $manager = AAM_Framework_Policy_Condition::get_instance();

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

        $this->assertTrue($manager->execute($policy['Condition']));

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
        $manager = AAM_Framework_Policy_Condition::get_instance();

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

        $this->assertTrue($manager->execute($policy['Condition']));

        $GLOBALS['varB'] = "no";

        $this->assertFalse($manager->execute($policy['Condition']));

        unset($GLOBALS['varA']);
        unset($GLOBALS['varB']);
    }

    /**
     * Between condition data provider
     *
     * @return array
     */
    public static function betweenDataProvider()
    {
        return [
            // Case #1
            [
                [
                    'Between' => [
                        10 => [ 5, 15 ]
                    ]
                ],
                true
            ],
            // Case #2
            [
                [
                    'Between' => [
                        10 => [
                            [ 1, 3 ], [ 5, 12 ]
                        ]
                    ]
                ],
                true
            ],
            // Case #3
            [
                [
                    'Between' => [
                        21 => [
                            [ 1, 3 ], [ 5, 12 ], [ 20, 21 ]
                        ]
                    ]
                ],
                true
            ],
            // Case #4
            [
                [
                    'Between' => [
                        1 => [ 5, 15 ]
                    ]
                ],
                false
            ]
        ];
    }

    /**
     * Equals condition data provider
     *
     * @return array
     */
    public static function equalsDataProvider()
    {
        // Note! Left side of the condition should never be boolean
        return [
            array(array('Equals' => array(0 => null)), false),
            array(array('Equals' => array(5 => 4)), false),
            array(array('Equals' => array(1 => 1)), true),
            array(array('Equals' => array(1 => '1')), false),
            array(array('Equals' => array('hello' => 'hello')), true),
            array(array('Equals' => array('hello' => 'hello1')), false),
        ];
    }

    /**
     * NotEquals condition data provider
     *
     * @return array
     */
    public static function notEqualsDataProvider()
    {
        // Note! Left side of the condition should never be boolean
        return array(
            array(array('NotEquals' => array(0 => null)), true),
            array(array('NotEquals' => array(5 => 4)), true),
            array(array('NotEquals' => array(1 => 1)), false),
            array(array('NotEquals' => array(1 => '1')), true),
            array(array('NotEquals' => array('2a' => 2)), true),
            array(array('NotEquals' => array('hello' => 'hello')), false),
            array(array('NotEquals' => array('hello' => 'hello1')), true),
        );
    }

    /**
     * Greater condition data provider
     *
     * @return array
     */
    public static function greaterDataProvider()
    {
        return array(
            array(array('Greater' => array(5 => 1)), true),
            array(array('Greater' => array(15 => 15)), false),
            array(array('Greater' => array(3 => 5)), false)
        );
    }

    /**
     * Less condition data provider
     *
     * @return array
     */
    public static function lessDataProvider()
    {
        return array(
            array(array('Less' => array(5 => 10)), true),
            array(array('Less' => array(15 => 15)), false),
            array(array('Less' => array(13 => 5)), false)
        );
    }

    /**
     * Greater or equals condition data provider
     *
     * @return array
     */
    public static function greaterOrEqualsDataProvider()
    {
        return array(
            array(array('GreaterOrEquals' => array(5 => 1)), true),
            array(array('GreaterOrEquals' => array(15 => 15)), true),
            array(array('GreaterOrEquals' => array(3 => 5)), false)
        );
    }

    /**
     * Less or equals condition data provider
     *
     * @return array
     */
    public static function lessOrEqualsDataProvider()
    {
        return array(
            array(array('LessOrEquals' => array(5 => 10)), true),
            array(array('LessOrEquals' => array(15 => 15)), true),
            array(array('LessOrEquals' => array(13 => 5)), false)
        );
    }

    /**
     * In condition data provider
     *
     * @return array
     */
    public static function inDataProvider()
    {
        return array(
            array(array('In' => array('test' => array('test', 'test1'))), true),
            array(array('In' => array(2 => array(2, 5, 7))), true),
            array(array('In' => array('no' => array('yes', 'maybe'))), false),
            array(array('In' => array('(*array)["a","b"]' => array('a', 'b'))), true),
            array(array('In' => array('(*array)["a","b"]' => array('a', 'c'))), false),
            array(array('In' => array('(*array)["a","b"]' => '(*array)["a","b"]')), true)
        );
    }

    /**
     * NotIn condition data provider
     *
     * @return array
     */
    public static function notInDataProvider()
    {
        return array(
            array(array('NotIn' => array('test' => array('test', 'test1'))), false),
            array(array('NotIn' => array(2 => array(2, 5, 7))), false),
            array(array('NotIn' => array('no' => array('yes', 'maybe'))), true),
            array(array('NotIn' => array('(*array)["a","b"]' => array('a', 'b'))), false),
            array(array('NotIn' => array('(*array)["a","b"]' => array('a', 'c'))), true),
            array(array('NotIn' => array('(*array)["a","b"]' => '(*array)["a","b"]')), false)
        );
    }

    /**
     * Like condition data provider
     *
     * @return array
     */
    public static function likeDataProvider()
    {
        return array(
            array(array('Like' => array('Lucy van Pelt' => 'Lucy*')), true),
            array(array('Like' => array('Lucy van Pelt' => '*Pelt')), true),
            array(array('Like' => array('Lucy van Pelt' => 'Lucy*Pelt')), true),
            array(array('Like' => array('Lucy van Pelt' => 'Johny*Pelt')), false)
        );
    }

    /**
     * NotLike condition data provider
     *
     * @return array
     */
    public static function notLikeDataProvider()
    {
        return array(
            array(array('NotLike' => array('Lucy van Pelt' => 'Lucy*')), false),
            array(array('NotLike' => array('Lucy van Pelt' => '*Pelt')), false),
            array(array('NotLike' => array('Lucy van Pelt' => 'Lucy*Pelt')), false),
            array(array('NotLike' => array('Lucy van Pelt' => 'Johny*Pelt')), true)
        );
    }

    /**
     * RegEx condition data provider
     *
     * @return array
     */
    public static function regExDataProvider()
    {
        return array(
            array(array('RegEx' => array('Hello World' => '/^[\w\s]+$/i')), true),
            array(array('RegEx' => array('Hello World!' => '/^[\w]+$/')), false)
        );
    }

    /**
     * Type casting data provider
     *
     * @return array
     */
    public static function typeCastingDataProvider()
    {
        return array(
            array(array('Equals' => array('(*int)1' => 1)), true),
            array(array('Equals' => array('(*bool)false' => false)), true),
            array(array('Equals' => array('(*boolean)true' => true)), true),
            array(array('Equals' => array('(*string)1' => '1')), true),
            array(array('Equals' => array('(*null)' => null)), true),
            array(array('In'     => array('(*array)[2,3]' => array(2,3))), true),
            array(array('Equals' => array('(*ip)192.168.1.1' => ip2long('192.168.1.1'))), true)
        );
    }

}