<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the AAM instance
 * 
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since  5.7.3
 */
class AAMTest extends TestCase {

    /**
     * Test that AAM loaded Visitor subject
     */
    public function testLoadedUserType() {
        $subject = AAM::api()->getUser();
        
        $this->assertSame('AAM_Core_Subject_Visitor', get_class($subject));
    }
}