<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\AccessPolicy;

use PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM_Service_AccessPolicy_HookResource;

/**
 * Test access policy Hook resource
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.9.17
 */
class HookResourceTest extends TestCase
{

    use ResetTrait;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookDenied()
    {
        add_filter('aam_testing_hook_denied', function(){});

        $this->assertEquals(true, has_filter('aam_testing_hook_denied'));

        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_denied', [
            'Effect' => 'deny'
        ]);

        $this->assertEquals(false, has_filter('aam_testing_hook_denied'));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookMerge()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_merge', [
            'Effect'   => 'merge',
            'Response' => [3, 4]
        ]);

        $this->assertEquals(
            [1, 2, 3, 4], apply_filters('aam_testing_hook_merge', [1, 2])
        );

        remove_all_filters('aam_testing_hook_merge');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverride()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => [3, 4]
        ]);

        $this->assertEquals(
            [3, 4], apply_filters('aam_testing_hook_override', [1, 2])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterEquals()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($key == a)'
        ]);

        $this->assertEquals(
            ['a' => 1], apply_filters('aam_testing_hook_override', ['c' => 0, 'a' => 1])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterNotEquals()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($key != a)'
        ]);

        $this->assertEquals(
            ['c' => 0], apply_filters('aam_testing_hook_override', ['c' => 0, 'a' => 1])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterContains()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($key *= te)'
        ]);

        $this->assertEquals(
            ['te1' => 0, 'uite2' => 1], apply_filters('aam_testing_hook_override', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterStartWith()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($key ^= te)'
        ]);

        $this->assertEquals(
            ['te1' => 0], apply_filters('aam_testing_hook_override', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterEndWith()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($key $= 2)'
        ]);

        $this->assertEquals(
            ['uite2' => 1], apply_filters('aam_testing_hook_override', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterValueComparison()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($value != 2)'
        ]);

        $this->assertEquals(
            ['te1' => 0, 'uite2' => 1], apply_filters('aam_testing_hook_override', ['te1' => 0, 'uite2' => 1, 'hjd' => 2])
        );

        remove_all_filters('aam_testing_hook_override');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testHookOverrideFilterComplexValueComparison()
    {
        AAM_Service_AccessPolicy_HookResource::parse('aam_testing_hook_override', [
            'Effect'   => 'override',
            'Response' => '&:filter($value.test[0].test != hello)'
        ]);

        $this->assertEquals(
            [[ 'test' => [ [ 'test' => 'no' ] ] ]],
            apply_filters('aam_testing_hook_override', [[ 'test' => [ [ 'test' => 'no' ] ] ], [ 'test' => [ [ 'test' => 'hello' ] ] ]])
        );

        remove_all_filters('aam_testing_hook_override');
    }

}