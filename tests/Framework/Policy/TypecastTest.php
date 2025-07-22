<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Policy;

use DateTime,
    AAM_Framework_Policy_Typecast,
    AAM\UnitTest\Utility\TestCase,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * Framework policy type-cast test
 */
final class TypecastTest extends TestCase
{

    /**
     * Test typecasting functionality
     *
     * @param string $expression
     * @param mixed  $value
     * @param mixed  $expected
     *
     * @return void
     */
    #[DataProvider('dataProvider')]
    public function testGetValueByPath($expression, $value, $expected)
    {
        $this->assertEquals($expected, AAM_Framework_Policy_Typecast::execute(
            $expression, $value
        ));
    }

    /**
     * Test IP Mask type-cast
     *
     * @return void
     */
    public function testIpMaskTypecast()
    {
        $range = AAM_Framework_Policy_Typecast::execute(
            '(*ip)192.168.0.1/24', '192.168.0.1/24'
        );

        $this->assertTrue($range('192.168.0.45'));
        $this->assertTrue($range('192.168.0.134'));
        $this->assertFalse($range('192.160.0.134'));
        $this->assertFalse($range('192.160.0.1'));
    }

    /**
     * Test date/time typecast
     *
     * @return void
     */
    public function testDateTypecast()
    {
        $date_a = AAM_Framework_Policy_Typecast::execute('(*date)now', 'now');
        $date_b = AAM_Framework_Policy_Typecast::execute('(*date)2025-01-01', '2025-01-01');
        $date_c = AAM_Framework_Policy_Typecast::execute('(*date)blah', 'blah');

        $this->assertNull($date_c);
        $this->assertEquals(get_class($date_a), DateTime::class);
        $this->assertEquals($date_a->format('Y-m-d'), date('Y-m-d'));
        $this->assertEquals(
            $date_b->format('Y-m-d'),
            date('Y-m-d', strtotime('2025-01-01'))
        );
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function dataProvider()
    {
        return [
            ['(*string)1', '1', '1'],
            ['(*string)hello', 'hello', 'hello'],
            ['(*string)null', 'null', 'null'],
            ['(*ip)10.10.10.1', '10.10.10.1', ip2long('10.10.10.1')],
            ['(*int)20', '20', 20],
            ['(*int)a', 'a', 0],
            ['(*float)3.2', '3.2', 3.2],
            ['(*float)0.56', '0.56', 0.56 ],
            ['(*numeric)1.2', '1.2', 1.2 ],
            ['(*numeric)46', '46', 46 ],
            ['(*numeric)e', 'e', 0 ],
            ['(*bool)true', 'true', true ],
            ['(*boolean)false', 'false', false],
            ['(*boolean)4', '4', false ],
            ['(*boolean)0', '0', false ],
            ['(*boolean)1', '1', true ],
            ['(*array)[1,2]', '[1,2]', [ 1, 2 ] ],
            ['(*null)', '', null ],
            ['(*null)null', 'null', null ],
            ['(*null)0', '0', '0' ]
        ];
    }

}