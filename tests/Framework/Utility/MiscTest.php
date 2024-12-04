<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Utility;

use AAM_Framework_Utility_Misc,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Misc" framework utility
 */
final class MiscTest extends TestCase
{

    /**
     * Test the is_base64_encoded method
     *
     * @return void
     */
    public function testBase64Encoded()
    {
        $this->assertTrue(AAM_Framework_Utility_Misc::is_base64_encoded(
            base64_encode('test')
        ));

        $this->assertFalse(AAM_Framework_Utility_Misc::is_base64_encoded('hello world'));
    }

    /**
     * Test the sanitize_slug method
     *
     * @return void
     */
    public function testSlugSanitization()
    {
        $this->assertEquals(
            'hello_test', AAM_Framework_Utility_Misc::sanitize_slug('Hello Test')
        );

        $this->assertEquals(
            'another_test', AAM_Framework_Utility_Misc::sanitize_slug('another$Test')
        );
    }

    /**
     * Test callback to slug conversion
     *
     * @return void
     */
    public function testCallbackToSlug()
    {
        $this->assertEquals(
            'testclass_run',
            AAM_Framework_Utility_Misc::callable_to_slug('TestClass::run')
        );

        $this->assertEquals(
            'aam_trigger',
            AAM_Framework_Utility_Misc::callable_to_slug([
                'AAM', 'trigger'
            ])
        );

        $this->assertEquals(
            'aam_unittest_framework_utility_misctest_test',
            AAM_Framework_Utility_Misc::callable_to_slug([ $this, 'test' ])
        );
    }

}