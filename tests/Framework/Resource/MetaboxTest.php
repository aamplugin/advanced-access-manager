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
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Metabox" framework resource
 */
final class MetaboxTest extends TestCase
{

    /**
     * Testing that we can properly initialize the metabox resource
     *
     * @return void
     */
    public function testMetaboxResourceInit()
    {
        // Verifying that toolbar item is allowed
        $this->assertFalse(
            AAM::api()->metaboxes()->is_restricted('post_excerpt_meta_box')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Metabox:post_excerpt_meta_box",
                "Effect": "deny"
            }
        }'));

        // Verifying that metabox is restricted
        $this->assertTrue(
            AAM::api()->metaboxes()->is_restricted('post_excerpt_meta_box')
        );
    }

    /**
     * Testing that we can properly initialize the metabox resource with ScreenId
     * property
     *
     * @return void
     */
    public function testMetaboxScreenIdResourceInit()
    {
        // Verifying that metabox is allowed
        $this->assertFalse(
            AAM::api()->metaboxes()->is_restricted('post_author_meta_box')
        );

        // Creating a new policy & attaching it to current access level
        $this->assertIsInt(AAM::api()->policies()->create('{
            "Statement": {
                "Resource": "Metabox:post_author_meta_box",
                "Effect": "deny",
                "ScreenId": "page"
            }
        }'));

        $service = AAM::api()->metaboxes();

        // Verifying that metabox is property restricted
        $this->assertFalse($service->is_restricted('post_author_meta_box'));
        $this->assertFalse($service->is_restricted('post_author_meta_box', 'post'));
        $this->assertTrue($service->is_restricted('post_author_meta_box', 'page'));
    }

}