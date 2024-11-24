<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Service;

use AAM,
    AAM\UnitTest\Utility\TestCase;

/**
 * Test class for the AAM "Access Denied Redirect" framework service
 */
final class AccessDeniedRedirectTest extends TestCase
{

    /**
     * Test that we can get and set redirect properly
     *
     * @return void
     */
    public function testSetGetRedirect()
    {
        $redirect_a = [ 'type' => 'login_redirect' ];
        $redirect_b = [
            'type'         => 'url_redirect',
            'redirect_url' => site_url() . '/some-page'
        ];

        // Setting redirect
        $this->assertEquals(
            $redirect_a,
            AAM::api()->access_denied_redirect()->set_redirect(
                'frontend', $redirect_a
            )
        );

        // Verifying that we are getting the same redirect back
        $this->assertEquals(
            $redirect_a,
            AAM::api()->access_denied_redirect()->get_redirect('frontend')
        );

        // Setting another redirect
        $this->assertEquals(
            [ 'type' => 'url_redirect', 'redirect_url' => '/some-page' ],
            AAM::api()->access_denied_redirect()->set_redirect(
                'backend', $redirect_b
            )
        );

        // Verifying that we are getting the same redirect back
        $this->assertEquals(
            [ 'type' => 'url_redirect', 'redirect_url' => '/some-page' ],
            AAM::api()->access_denied_redirect()->get_redirect('backend')
        );
    }


}