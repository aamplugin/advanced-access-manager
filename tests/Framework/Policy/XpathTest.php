<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use AAM_Framework_Policy_Xpath,
    AAM\UnitTest\Utility\TestCase,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * Framework policy XPath test
 */
final class XpathTest extends TestCase
{

    /**
     * Test xpath functionality
     *
     * @param string $xpath
     * @param mixed  $source
     * @param mixed  $expected
     *
     * @return void
     */
    #[DataProvider('dataProvider')]
    public function testGetValueByPath($xpath, $source, $expected)
    {
        $this->assertEquals(
            $expected,
            AAM_Framework_Policy_Xpath::get_value_by_xpath($source, $xpath)
        );
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function dataProvider()
    {
        $obj = new class {
            public $prop = 'nice';

            public function get_it()
            {
                return [ 'test' => 'y' ];
            }
        };

        return [
            [ 'prop', (object)[ 'prop' => 'test' ], 'test' ],
            [ '1', [ 'a', 'b' ], 'b' ],
            [ 'a[1].prop', [ 'a' => [ '1', [ 'prop' => 'yes' ] ] ], 'yes' ],
            [ 'prop', $obj, 'nice' ],
            [ 'get_it.test', $obj, 'y' ]
        ];
    }

}