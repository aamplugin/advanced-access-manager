<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Core;

use AAM_Core_API,
    AAM_Core_Cache,
    AAM_Framework_Manager,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM core service functionality
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.9.17
 */
class CacheTest extends TestCase
{
    use ResetTrait;

    /**
     * Test cache reset
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public function testCacheReset()
    {
        AAM_Core_Cache::set('test', 1);

        $this->assertTrue(
            array_key_exists(
                'test',
                AAM_Core_API::getOption(AAM_Core_Cache::DB_OPTION)
            )
        );

        AAM_Core_Cache::reset();

        $this->assertFalse(
            array_key_exists(
                'test',
                AAM_Core_API::getOption(AAM_Core_Cache::DB_OPTION)
            )
        );
    }

    /**
     * Test cache overflow
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public function testCacheOverflow()
    {
        AAM_Framework_Manager::configs()->set_config(
            'core.settings.cache.capability', 2
        );

        AAM_Core_Cache::set('test-1', 1);
        AAM_Core_Cache::set('test-2', 2);

        $this->assertEquals(1, AAM_Core_Cache::get('test-1'));

        AAM_Core_Cache::set('test-3', 3);

        $this->assertFalse(AAM_Core_Cache::get('test-1', false));

        AAM_Framework_Manager::configs()->reset_config(
            'core.settings.cache.capability'
        );
        AAM_Core_Cache::reset();
    }

}