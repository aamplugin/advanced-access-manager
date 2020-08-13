<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Service\Content;

use WP_REST_Request,
    PHPUnit\Framework\TestCase,
    AAM\UnitTest\Libs\ResetTrait,
    AAM\UnitTest\Libs\AuthUserTrait;

/**
 * Test cases for the Content Service itself
 *
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 6.6.1
 */
class ContentServiceTest extends TestCase
{
    use ResetTrait,
        AuthUserTrait;

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

    /**
     * Testing that AAM does not interfere with WP core password setting
     *
     * @link https://github.com/aamplugin/advanced-access-manager/issues/137
     *
     * @return void
     * @access public
     * @version 6.6.1
     */
    public function testPasswordCanBeSet()
    {
        $server = rest_get_server();

        $request = new WP_REST_Request('POST', '/wp/v2/posts/' . AAM_UNITTEST_POST_ID);
        $request->set_param('password', '1234567');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals($data['password'], '1234567');

        // Reset
        wp_update_post(array(
            'ID'            => AAM_UNITTEST_POST_ID,
            'post_password' => null
        ));
    }

}