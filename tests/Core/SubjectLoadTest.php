<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use PHPUnit\Framework\TestCase;

/**
 * Test if proper subject is picked correctly
 * 
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.0
 */
class SubjectLoadTest extends TestCase
{

    /**
     * Test that AAM loaded Visitor subject
     * 
     * AAM has to load Visitor subject when there is no indicators or authentication
     */
    public function testLoadedVisitorType()
    {
        $subject = AAM::getUser();

        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));
    }
}
