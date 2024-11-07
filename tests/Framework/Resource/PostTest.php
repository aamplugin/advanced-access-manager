<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM,
    AAM\UnitTest\Utility\TestCase,
    AAM_Framework_Service_Settings;

/**
 * Test class for the AAM "Post" framework resource
 */
final class PostTest extends TestCase
{

    /**
     * Test that defined LIST permission is properly managed by framework
     *
     * @return void
     *
     * @access public
     */
    public function testHiddenOnAllAreasPost()
    {
        // Let's create few posts and hide one of them
        $post_b = $this->createPost();

        $service = AAM::api()->content();

        // Hide the post B
        $this->assertTrue($service->post($post_b)->add_permission('list', 'deny'));

        // Verifying that post B is hidden on all areas
        $post = $service->post($post_b);

        $this->assertTrue($post->is_hidden_on('frontend'));
        $this->assertTrue($post->is_hidden_on('backend'));
        $this->assertTrue($post->is_hidden_on('api'));
        $this->assertTrue($post->is_hidden());

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'list' => [
                'effect' => 'deny',
                'on' => [
                    'frontend',
                    'backend',
                    'api'
                ]
            ]
        ], $options['visitor']['post'][$post_b]);

    }

    /**
     * Test that defined LIST permission is properly managed by framework for
     * selected areas
     *
     * @return void
     *
     * @access public
     */
    public function testHiddenOnSelectedAreasPost()
    {
        // Let's create few posts and hide one of them
        $post_a = $this->createPost();

        $service = AAM::api()->content();

        // Hide the post B
        $this->assertTrue($service->post($post_a)->add_permission('list', [
            'effect' => 'deny',
            'on'     => [ 'backend' ]
        ]));

        // Verifying that post B is hidden on all areas
        $post = $service->post($post_a);

        $this->assertFalse($post->is_hidden_on('frontend'));
        $this->assertTrue($post->is_hidden_on('backend'));
        $this->assertFalse($post->is_hidden_on('api'));

        // Read raw post settings and ensure they are stored properly
        $options = $this->readWpOption(AAM_Framework_Service_Settings::DB_OPTION);

        $this->assertEquals([
            'list' => [
                'effect' => 'deny',
                'on' => [
                    'backend',
                ]
            ]
        ], $options['visitor']['post'][$post_a]);

    }

}