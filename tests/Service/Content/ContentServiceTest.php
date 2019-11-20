<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use AAM,
    AAM_Service_Content,
    AAM_Core_Object_Post,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait;

/**
 * Test cases for the Content Service itself
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.0.1
 */
class ContentServiceTest extends TestCase
{
    use ResetTrait;

    /**
     * Making sure that original status of commenting is preserved when no access
     * settings are defined
     *
     * @link https://forum.aamplugin.com/d/353-comment-system-activated
     *
     * @access public
     * @version 6.0.1
     */
    public function testCommentingStatusPreserved()
    {
        $this->assertTrue(apply_filters('comments_open', true, AAM_UNITTEST_PAGE_ID));
        $this->assertFalse(apply_filters('comments_open', false, AAM_UNITTEST_PAGE_ID));
    }

}