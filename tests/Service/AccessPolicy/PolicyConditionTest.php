<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use AAM_Core_Policy_Condition,
    PHPUnit\Framework\TestCase;

/**
 * Test policy condition evaluator
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class PolicyConditionTest extends TestCase
{
    /**
     * Validate Between condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider betweenDataProvider
     * @version 6.0.0
     */
    public function testBetweenCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Between condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function betweenDataProvider()
    {
        return array(
            array(array('Between' => array(10 => array(5, 15))), true),
            array(array('Between' => array(10 => array(array(1, 3), array(5, 12)))), true),
            array(array('Between' => array(21 => array(array(1, 3), array(5, 12), array(20, 21)))), true),
            array(array('Between' => array(1 => array(5, 15))), false)
        );
    }

    /**
     * Validate Equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider equalsDataProvider
     * @version 6.0.0
     */
    public function testEqualsCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Equals condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function equalsDataProvider()
    {
        // Note! Left side of the condition should never be boolean
        return array(
            array(array('Equals' => array(0 => null)), false),
            array(array('Equals' => array(5 => 4)), false),
            array(array('Equals' => array(1 => 1)), true),
            array(array('Equals' => array(1 => '1')), false),
            array(array('Equals' => array('hello' => 'hello')), true),
            array(array('Equals' => array('hello' => 'hello1')), false),
        );
    }

    /**
     * Validate NotEquals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider notEqualsDataProvider
     * @version 6.0.0
     */
    public function testNotEqualsCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * NotEquals condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function notEqualsDataProvider()
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
     * Validate Greater condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider greaterDataProvider
     * @version 6.0.0
     */
    public function testGreaterCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Greater condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function greaterDataProvider()
    {
        return array(
            array(array('Greater' => array(5 => 1)), true),
            array(array('Greater' => array(15 => 15)), false),
            array(array('Greater' => array(3 => 5)), false)
        );
    }

    /**
     * Validate Less condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider lessDataProvider
     * @version 6.0.0
     */
    public function testLessCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Less condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function lessDataProvider()
    {
        return array(
            array(array('Less' => array(5 => 10)), true),
            array(array('Less' => array(15 => 15)), false),
            array(array('Less' => array(13 => 5)), false)
        );
    }

    /**
     * Validate greater or equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider greaterOrEqualsDataProvider
     * @version 6.0.0
     */
    public function testGreaterOrEqualsCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Greater or equals condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function greaterOrEqualsDataProvider()
    {
        return array(
            array(array('GreaterOrEquals' => array(5 => 1)), true),
            array(array('GreaterOrEquals' => array(15 => 15)), true),
            array(array('GreaterOrEquals' => array(3 => 5)), false)
        );
    }

    /**
     * Validate Less or equals condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider lessOrEqualsDataProvider
     * @version 6.0.0
     */
    public function testLessOrEqualsCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Less or equals condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function lessOrEqualsDataProvider()
    {
        return array(
            array(array('LessOrEquals' => array(5 => 10)), true),
            array(array('LessOrEquals' => array(15 => 15)), true),
            array(array('LessOrEquals' => array(13 => 5)), false)
        );
    }

    /**
     * Validate In condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider inDataProvider
     * @version 6.0.0
     */
    public function testInCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * In condition data provider
     *
     * @return void
     *
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/123
     * @since 6.0.0 Initial implementation of the testcase
     *
     * @access public
     * @version 6.5.3
     */
    public function inDataProvider()
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
     * Validate NotIn condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider notInDataProvider
     * @version 6.0.0
     */
    public function testNotInCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * NotIn condition data provider
     *
     * @return void
     *
     * @since 6.5.3 https://github.com/aamplugin/advanced-access-manager/issues/123
     * @since 6.0.0 Initial implementation of the testcase
     *
     * @access public
     * @version 6.5.3
     */
    public function notInDataProvider()
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
     * Validate Like condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider likeDataProvider
     * @version 6.0.0
     */
    public function testLikeCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Like condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function likeDataProvider()
    {
        return array(
            array(array('Like' => array('Lucy van Pelt' => 'Lucy*')), true),
            array(array('Like' => array('Lucy van Pelt' => '*Pelt')), true),
            array(array('Like' => array('Lucy van Pelt' => 'Lucy*Pelt')), true),
            array(array('Like' => array('Lucy van Pelt' => 'Johny*Pelt')), false)
        );
    }

    /**
     * Validate NotLike condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider notLikeDataProvider
     * @version 6.0.0
     */
    public function testNotLikeCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * NotLike condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function notLikeDataProvider()
    {
        return array(
            array(array('NotLike' => array('Lucy van Pelt' => 'Lucy*')), false),
            array(array('NotLike' => array('Lucy van Pelt' => '*Pelt')), false),
            array(array('NotLike' => array('Lucy van Pelt' => 'Lucy*Pelt')), false),
            array(array('NotLike' => array('Lucy van Pelt' => 'Johny*Pelt')), true)
        );
    }

    /**
     * Validate RegEx condition evaluator
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider regExDataProvider
     * @version 6.0.0
     */
    public function testRegExCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * RegEx condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function regExDataProvider()
    {
        return array(
            array(array('RegEx' => array('Hello World' => '/^[\w\s]+$/i')), true),
            array(array('RegEx' => array('Hello World!' => '/^[\w]+$/')), false)
        );
    }

    /**
     * Validate condition type casting
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider typeCastingDataProvider
     * @version 6.0.0
     */
    public function testTypeCasting($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Type casting data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function typeCastingDataProvider()
    {
        return array(
            array(array('Equals' => array('(*int)1' => 1)), true),
            array(array('Equals' => array('(*bool)false' => false)), true),
            array(array('Equals' => array('(*boolean)true' => true)), true),
            array(array('Equals' => array('(*string)1' => '1')), true),
            array(array('Equals' => array('(*null)' => null)), true),
            array(array('Equals' => array('(*array)[2,3]' => array(2,3))), true),
            array(array('Equals' => array('(*ip)192.168.1.1' => ip2long('192.168.1.1'))), true)
        );
    }

    /**
     * Validate complex condition
     *
     * @param array   $condition
     * @param boolean $expectedResult
     *
     * @return void
     *
     * @access public
     * @dataProvider complexDataProvider
     * @version 6.0.0
     */
    public function testComplexCondition($condition, $expectedResult)
    {
        $manager = AAM_Core_Policy_Condition::getInstance();

        $this->assertEquals($expectedResult, $manager->evaluate($condition));
    }

    /**
     * Complex condition data provider
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function complexDataProvider()
    {
        return array(
            array(array(
                'Equals'    => array('(*int)1' => 1),
                'NotEquals' => array('2a' => 2)
            ), true)
        );
    }

}