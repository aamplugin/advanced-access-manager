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
    use ResetTrait;

    /**
     * Targeting post ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $post_id;

    /**
     * Targeting page ID
     *
     * @var int
     *
     * @access protected
     * @version 6.7.0
     */
    protected static $page_id;

    /**
     * @inheritdoc
     */
    private static function _setUpBeforeClass()
    {
        // Set current User. Emulate that this is admin login
        wp_set_current_user(AAM_UNITTEST_ADMIN_USER_ID);

        // Setup a default post
        self::$post_id = wp_insert_post(array(
            'post_title'  => 'Content Service Post',
            'post_name'   => 'content-service-post',
            'post_status' => 'publish'
        ));

        // Setup a default page
        self::$page_id = wp_insert_post(array(
            'post_title'  => 'Content Service Page',
            'post_name'   => 'content-service-page',
            'post_type'   => 'page',
            'post_status' => 'publish'
        ));
    }

    /**
     * @inheritdoc
     */
    private static function _tearDownAfterClass()
    {
        // Unset the forced user
        wp_set_current_user(0);
    }

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
        $this->assertTrue(apply_filters('comments_open', true, self::$page_id));
        $this->assertFalse(apply_filters('comments_open', false, self::$page_id));
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

        $request = new WP_REST_Request('POST', '/wp/v2/posts/' . self::$post_id);
        $request->set_param('password', '1234567');

        $data = $server->dispatch($request)->get_data();

        $this->assertEquals($data['password'], '1234567');

        // Reset
        wp_update_post(array(
            'ID'            => self::$post_id,
            'post_password' => null
        ));
    }

}