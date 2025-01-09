<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase,
    PHPUnit\Framework\Attributes\DataProvider;

/**
 * AAM Hooks service test suite
 */
final class HooksTest extends TestCase
{
    /**
     * Test "deny" hook for a very specific priority
     *
     * @return void
     */
    public function testDenyEffectWithExactPriority()
    {
        global $wp_filter;

        // Adding dummy filter
        add_filter('aam_test_filter', function() {
            return 5;
        }, 20);

        $this->assertEquals(5, apply_filters('aam_test_filter', null));
        $this->assertTrue(isset($wp_filter['aam_test_filter']));

        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 20,
            'effect'   => 'deny'
        ]);

        $this->assertEquals(null, apply_filters('aam_test_filter', null));
    }

    /**
     * Test "deny" hook for a different priority
     *
     * @return void
     */
    public function testDenyEffectWithDifferentPriority()
    {
        // Adding dummy filter
        add_filter('aam_test_filter', function() {
            return 5;
        }, 10);

        $this->assertEquals(5, apply_filters('aam_test_filter', null));

        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 20,
            'effect'   => 'deny'
        ]);

        $this->assertEquals(5, apply_filters('aam_test_filter', null));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Test "merge" effect
     *
     * @return void
     */
    public function testMergeEffect()
    {
        global $wp_filter;

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function($v) {
            return $v;
        }, 1);

        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 10,
            'effect'   => 'merge',
            'return'   => [ 3, 4 ]
        ]);

        $this->assertTrue(isset($wp_filter['aam_test_filter']->callbacks[10]));
        $this->assertEquals([1, 2, 3, 4], apply_filters('aam_test_filter', [1, 2]));
        $this->assertTrue(isset($wp_filter['aam_test_filter']->callbacks[10]));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Test "alter" effect
     *
     * @return void
     */
    public function testAlterEffect()
    {
        $GLOBALS['test_replace'] = 0;

        add_filter('aam_test_filter', function($v) {
            $GLOBALS['test_replace']++;

            return $v;
        });

        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 10,
            'effect'   => 'alter',
            'return'   => [ 3, 4 ]
        ]);

        $this->assertEquals([3, 4], apply_filters('aam_test_filter', 2));
        $this->assertEquals(1, $GLOBALS['test_replace']);

        // Also testing that the order of the registered filter is correct
        add_filter('aam_test_filter', function() {
            return 5;
        }, 20);

        $this->assertEquals(5, apply_filters('aam_test_filter', 2));

        remove_all_filters('aam_test_filter');
        unset($GLOBALS['test_replace']);
    }

    /**
     * Test "alter" effect with filters
     *
     * @param string $filter
     * @param mixed  $expected
     * @param mixed  $input
     *
     * @return void
     */
    #[DataProvider('alterFiltersProvider')]
    public function testAlterEffectWithFilters($filter, $expected, $input)
    {
        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 10,
            'effect'   => 'alter',
            'return'   => $filter
        ]);

        $this->assertEquals($expected, apply_filters('aam_test_filter', $input));

        remove_all_filters('aam_test_filter');
    }

    /**
     * Test "replace" effect
     *
     * @return void
     */
    public function testReplaceEffect()
    {
        $GLOBALS['test_replace'] = 0;

        add_filter('aam_test_filter', function() {
            $GLOBALS['test_replace']++;
        });

        // Register new listener
        AAM::api()->hooks()->listen([
            'name'     => 'aam_test_filter',
            'priority' => 10,
            'effect'   => 'replace',
            'return'   => 'yes'
        ]);

        $this->assertEquals('yes', apply_filters('aam_test_filter', false));
        $this->assertEquals(0, $GLOBALS['test_replace']);

        remove_all_filters('aam_test_filter');
        unset($GLOBALS['test_replace']);
    }

    /**
     * Various cases for "alter" effect with filters
     *
     * @return array
     */
    public static function alterFiltersProvider()
    {
        return [
            // Case #1. Test "alter" effect with key equals filter
            [
                '&:filter($key == a)',
                [ 'a' => 1 ],
                [ 'c' => 0, 'a' => 1 ]
            ],
            // Case #2. Test "alter" effect with key not equals filter
            [
                '&:filter($key != a)',
                [ 'c' => 0 ],
                [ 'c' => 0, 'a' => 1 ]
            ],
            // Case #3. Test "alter" effect with contains filter
            [
                '&:filter($key *= te)',
                [ 'te1' => 0, 'uite2' => 1 ],
                [ 'te1' => 0, 'uite2' => 1, 'hjd' => 2 ]
            ],
            // Case #4. Test "alter" effect with starts with filter
            [
                '&:filter($key ^= te)',
                [ 'te1' => 0 ],
                [ 'te1' => 0, 'uite2' => 1, 'hjd' => 2 ]
            ],
            // Case #5. Test "alter" effect with ends with filter
            [
                '&:filter($key $= 2)',
                [ 'uite2' => 1 ],
                [ 'te1' => 0, 'uite2' => 1, 'hjd' => 2 ]
            ],
            // Case #6. Test "alter" effect with value not equals filter
            [
                '&:filter($value != 2)',
                [ 'te1' => 0, 'uite2' => 1 ],
                [ 'te1' => 0, 'uite2' => 1, 'hjd' => 2 ]
            ],
            // Case #7. Test "alter" effect with value equals filter
            [
                '&:filter($value == 2)',
                [ 'hjd' => 2 ],
                [ 'te1' => 0, 'uite2' => 1, 'hjd' => 2 ]
            ],
            // Case #8. Test "alter" effect with complex data
            [
                '&:filter($value.test[0].test != hello)',
                [ [ 'test' => [ [ 'test' => 'no' ] ] ] ],
                [ [ 'test' => [ [ 'test' => 'no' ] ] ], [ 'test' => [ [ 'test' => 'hello' ] ] ] ]
            ],
            // Case #9. Test "alter" effect with pipeline of filters
            [
                [ '&:filter($key != hello)', '&:filter($value > 5)' ],
                [ 'c' => 10 ],
                [ 'hello' => 1, 'a' => 2, 'b' => 5, 'c' => 10 ]
            ],
        ];
    }

}