<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Core;

use PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test AAM core service functionality
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class CoreServiceTest extends TestCase
{
    use ResetTrait;

    /**
     * Test that all AAM related labels are properly escaped to mitigate XSS
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function testEscapeTranslation()
    {
        $escaped = __('<script>alert(1);</script>', AAM_KEY);
        $this->assertEquals($escaped, '&lt;script&gt;alert(1);&lt;/script&gt;');
    }

}